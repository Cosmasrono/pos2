<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\MpesaPayment;
use Illuminate\Support\Str;

class SalesService
{
    public function createSale(array $data): Sale
    {
        // Determine branch: use user's branch if they have one, otherwise use main branch
        $branchId = auth()->user()?->branch_id;
        if (!$branchId) {
            $mainBranch = \App\Models\Branch::where('is_main', true)->first();
            $branchId = $mainBranch?->id;
        }

        $sale = Sale::create([
            'receipt_number' => $this->generateReceiptNumber(),
            'cashier_id' => $data['cashier_id'],
            'branch_id' => $branchId,
            'customer_id' => $data['customer_id'] ?? null,
            'status' => 'completed',
            'subtotal' => $data['subtotal'],
            'promotion_id' => $data['promotion_id'] ?? null,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'trade_in_amount' => $data['trade_in_amount'] ?? 0,
            'total_amount' => $data['total_amount'],
            'primary_payment_method' => $data['primary_payment_method'],
            'cash_paid' => $data['cash_paid'] ?? 0,
            'mpesa_paid' => $data['mpesa_paid'] ?? 0,
            'card_paid' => $data['card_paid'] ?? 0,
            'change_amount' => $data['change_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
        ]);

        // Add sale items and update inventory
        foreach ($data['items'] as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $item['line_total'],
                'discount_per_item' => $item['discount_per_item'] ?? 0,
            ]);

            // Get the product and branch from the sale
            $product = Product::find($item['product_id']);
            $branchId = $sale->branch_id;

            // Reduce branch-specific inventory
            if ($branchId) {
                $branchStock = $product->branchStocks()
                    ->where('branch_id', $branchId)
                    ->first();
                
                if ($branchStock) {
                    $branchStock->quantity_in_stock -= $item['quantity'];
                    $branchStock->save();
                }
            }

            // Reduce total inventory
            $product->quantity_in_stock -= $item['quantity'];
            $product->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $item['product_id'],
                'branch_id' => $branchId,
                'type' => 'sale',
                'quantity' => -$item['quantity'],
                'notes' => "Sale #{$sale->receipt_number}",
                'user_id' => $data['cashier_id'],
            ]);
        }

        // Handle Trade-ins
        if (isset($data['trade_ins']) && !empty($data['trade_ins'])) {
            $tradeInCategory = \App\Models\Category::firstOrCreate(['name' => 'Trade-in'], ['description' => 'Items received via trade-in']);
            
            foreach ($data['trade_ins'] as $tradeInData) {
                // Create a new product for the traded-in item
                $tradeInProduct = Product::create([
                    'name' => $tradeInData['model_name'] . ' (Trade-in)',
                    'sku' => 'TRD-' . strtoupper(Str::random(8)),
                    'barcode' => $tradeInData['imei_serial'] ?? null,
                    'cost_price' => $tradeInData['value'],
                    'selling_price' => $tradeInData['value'] * 1.2, // Default 20% markup
                    'quantity_in_stock' => 1,
                    'category_id' => $tradeInCategory->id,
                    'is_active' => true,
                    'description' => "Traded in. Condition: " . ($tradeInData['condition'] ?? 'Unknown') . ". IMEI/Serial: " . ($tradeInData['imei_serial'] ?? 'N/A'),
                ]);

                // Record the trade-in
                \App\Models\TradeIn::create([
                    'sale_id' => $sale->id,
                    'model_name' => $tradeInData['model_name'],
                    'imei_serial' => $tradeInData['imei_serial'] ?? null,
                    'value' => $tradeInData['value'],
                    'condition' => $tradeInData['condition'] ?? null,
                    'product_id' => $tradeInProduct->id,
                ]);

                // Record stock movement for the new product
                StockMovement::create([
                    'product_id' => $tradeInProduct->id,
                    'type' => 'purchase', // Trating as a purchase for inventory purposes
                    'quantity' => 1,
                    'notes' => "Trade-in from Sale #{$sale->receipt_number}",
                    'user_id' => $data['cashier_id'],
                ]);
            }
        }

        // If M-PESA payment, create payment record
        if ($data['primary_payment_method'] === 'mpesa' && isset($data['mpesa_phone'])) {
            MpesaPayment::create([
                'sale_id' => $sale->id,
                'phone_number' => $data['mpesa_phone'],
                'amount' => $data['mpesa_paid'],
                'status' => 'pending',
            ]);
        }

        return $sale;
    }

    public function refundSale(Sale $sale, array $refundData): void
    {
        $sale->update(['status' => 'refunded']);

        // Refund items and restore inventory
        foreach ($refundData['items'] as $item) {
            $product = Product::find($item['product_id']);
            $product->quantity_in_stock += $item['quantity'];
            $product->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $item['product_id'],
                'type' => 'return',
                'quantity' => $item['quantity'],
                'notes' => "Refund for Sale #{$sale->receipt_number}",
                'user_id' => auth()->id(),
            ]);
        }
    }

    public function processMpesaPayment(MpesaPayment $payment, string $transactionCode): void
    {
        $payment->update([
            'transaction_code' => $transactionCode,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function failMpesaPayment(MpesaPayment $payment, string $errorMessage): void
    {
        $payment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);

        // Cancel the sale if only payment method
        if ($payment->sale && $payment->sale->mpesa_paid == $payment->amount && $payment->sale->cash_paid == 0) {
            $payment->sale->update(['status' => 'cancelled']);
        }
    }

    private function generateReceiptNumber(): string
    {
        $prefix = 'RCP-' . date('Ymd');
        $lastSale = Sale::where('receipt_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastSale ? intval(substr($lastSale->receipt_number, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function getDailySales(\DateTime $date): array
    {
        return [
            'total_sales' => Sale::whereDate('created_at', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->sum('total_amount'),
            'transaction_count' => Sale::whereDate('created_at', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->count(),
            'cash_sales' => Sale::whereDate('created_at', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->sum('cash_paid'),
            'mpesa_sales' => Sale::whereDate('created_at', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->sum('mpesa_paid'),
            'card_sales' => Sale::whereDate('created_at', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->sum('card_paid'),
        ];
    }
}
