<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\SupplierPayment;
use App\Models\StockMovement;

class PurchaseService
{
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => $this->generatePONumber(),
            'supplier_id' => $data['supplier_id'],
            'status' => 'pending',
            'order_date' => now()->date(),
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'created_by' => auth()->id(),
            'total_cost' => 0,
        ]);

        $totalCost = 0;

        // Add items
        foreach ($data['items'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $item['product_id'],
                'quantity_ordered' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
            ]);

            $totalCost += $item['quantity'] * $item['unit_cost'];
        }

        $po->update(['total_cost' => $totalCost]);

        return $po;
    }

    public function receiveStock(PurchaseOrder $po, array $receivedItems): void
    {
        $po->update(['status' => 'received', 'received_date' => now(), 'received_by' => auth()->id()]);

        foreach ($receivedItems as $item) {
            $poItem = PurchaseOrderItem::find($item['id']);
            $poItem->update([
                'quantity_received' => $item['quantity_received'],
                'quantity_damaged' => $item['quantity_damaged'] ?? 0,
            ]);

            // Update product stock
            $product = $poItem->product;
            $product->quantity_in_stock += $item['quantity_received'];
            $product->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'purchase',
                'quantity' => $item['quantity_received'],
                'notes' => "Received from PO #{$po->po_number}",
                'user_id' => auth()->id(),
            ]);

            // Record damage if any
            if (($item['quantity_damaged'] ?? 0) > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'damage',
                    'quantity' => -($item['quantity_damaged']),
                    'notes' => "Damaged in delivery from PO #{$po->po_number}",
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }

    public function recordPayment(PurchaseOrder $po, array $paymentData): SupplierPayment
    {
        return SupplierPayment::create([
            'supplier_id' => $po->supplier_id,
            'purchase_order_id' => $po->id,
            'type' => $paymentData['type'],
            'amount' => $paymentData['amount'],
            'payment_method' => $paymentData['payment_method'],
            'payment_date' => $paymentData['payment_date'] ?? now()->date(),
            'reference_number' => $paymentData['reference_number'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'recorded_by' => auth()->id(),
        ]);
    }

    public function getPendingOrders(): array
    {
        return PurchaseOrder::where('status', 'pending')
            ->orderBy('expected_delivery_date')
            ->get()
            ->toArray();
    }

    public function getSupplierBalance(Supplier $supplier): array
    {
        $totalPurchases = $supplier->purchaseOrders()
            ->where('status', '!=', 'cancelled')
            ->sum('total_cost');

        $totalPaid = $supplier->payments()->sum('amount');

        return [
            'total_purchases' => $totalPurchases,
            'total_paid' => $totalPaid,
            'balance_due' => $totalPurchases - $totalPaid,
        ];
    }

    private function generatePONumber(): string
    {
        $prefix = 'PO-' . date('Ymd');
        $lastPO = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastPO ? intval(substr($lastPO->po_number, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
