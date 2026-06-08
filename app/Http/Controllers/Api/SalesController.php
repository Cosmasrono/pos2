<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'primary_payment_method' => 'required|in:cash,mpesa,card',
            'cash_paid' => 'numeric|min:0',
            'mpesa_paid' => 'numeric|min:0',
            'mpesa_phone' => 'nullable|required_if:primary_payment_method,mpesa|string',
            'card_paid' => 'numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'discount_amount' => 'numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'change_amount' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'shift_id' => 'nullable|exists:shifts,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'items.*.discount_per_item' => 'numeric|min:0',
        ]);

        $validated['cashier_id'] = auth()->id();

        $sale = $this->salesService->createSale($validated);

        return response()->json($sale, 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load('items.product', 'customer', 'cashier');
        return response()->json($sale);
    }

    public function dailySales(Request $request): JsonResponse
    {
        $date = $request->get('date') ? new \DateTime($request->get('date')) : new \DateTime();
        $summary = $this->salesService->getDailySales($date);

        return response()->json($summary);
    }

    public function cashierSales(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->get('end_date') ?? now()->endOfDay()->format('Y-m-d');

        $sales = Sale::where('cashier_id', auth()->id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total, AVG(total_amount) as average')
            ->first();

        return response()->json($sales);
    }

    public function processReturn(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        $this->salesService->refundSale($sale, $validated);

        return response()->json(['message' => 'Return processed successfully']);
    }

    public function seedTestData(Request $request): JsonResponse
    {
        try {
            // Create test products
            $products = \App\Models\Product::factory(10)->create();

            // Create test customers
            $customers = \App\Models\Customer::factory(5)->create();

            // Create test sales with items
            $sales = [];
            for ($i = 0; $i < 3; $i++) {
                $saleData = [
                    'customer_id' => $customers->random()->id,
                    'cashier_id' => auth()->id(),
                    'primary_payment_method' => 'cash',
                    'cash_paid' => 5000,
                    'mpesa_paid' => 0,
                    'card_paid' => 0,
                    'subtotal' => 4500,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 4500,
                    'change_amount' => 500,
                    'status' => 'completed',
                ];

                $sale = \App\Models\Sale::create($saleData);

                // Add 2-3 items per sale
                for ($j = 0; $j < random_int(2, 3); $j++) {
                    \App\Models\SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $products->random()->id,
                        'quantity' => random_int(1, 3),
                        'unit_price' => random_int(500, 2000),
                        'line_total' => random_int(500, 4500),
                        'discount_per_item' => 0,
                    ]);
                }

                $sales[] = $sale;
            }

            return response()->json([
                'message' => 'Test data seeded successfully',
                'products_created' => count($products),
                'customers_created' => count($customers),
                'sales_created' => count($sales),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error seeding test data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
