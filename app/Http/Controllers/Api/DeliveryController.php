<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\DeliveryOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class DeliveryController extends Controller
{
    /**
     * Login endpoint for delivery system
     * GET /api/delivery/login
     * POST /api/delivery/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Optional: Check if user is authorized for delivery API
        // if (!in_array($user->role, ['delivery', 'admin', 'manager'])) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized: insufficient permissions',
        //     ], 403);
        // }

        $token = $user->createToken('delivery-api', ['delivery:access'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    /**
     * Get all delivery products with inventory
     * GET /api/delivery/delivery/products
     * 
     * Query params:
     * - search: Search by name, sku, barcode
     * - branch_id: Filter by branch
     * - category_id: Filter by category
     * - per_page: Pagination (default 50)
     */
    public function getDeliveryProducts(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $search = $request->get('search');
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        $query = Product::with(['category', 'branchStocks.branch'])
            ->where('is_active', true);

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Branch filter
        if ($branchId) {
            $query->whereHas('branchStocks', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->where('quantity_in_stock', '>', 0);
            });
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn($p) => $this->formatProduct($p)),
            'pagination' => [
                'total' => $products->total(),
                'count' => count($products),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ]
        ], 200);
    }

    /**
     * Get inventory status
     * GET /api/delivery/inventory
     */
    public function getInventory(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');
        $lowStockOnly = (bool) $request->get('low_stock', false);

        $query = Product::with(['category', 'branchStocks.branch'])
            ->where('is_active', true);

        if ($lowStockOnly) {
            $query->whereRaw('quantity_in_stock <= reorder_level');
        }

        $products = $query->get();

        $inventory = $products->map(function ($product) use ($branchId) {
            $stocks = $product->branchStocks;
            
            if ($branchId) {
                $stocks = $stocks->where('branch_id', $branchId);
            }

            $totalStock = $stocks->sum('quantity_in_stock');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Uncategorized',
                'total_stock' => $totalStock,
                'reorder_level' => $product->reorder_level,
                'is_low_stock' => $totalStock > 0 && $totalStock <= $product->reorder_level,
                'selling_price' => (float) $product->selling_price,
                'branches' => $stocks->map(fn($s) => [
                    'branch_id' => $s->branch_id,
                    'branch_name' => $s->branch->name,
                    'quantity' => $s->quantity_in_stock,
                ])->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'total_items' => $inventory->count(),
            'data' => $inventory
        ], 200);
    }

    /**
     * Get delivery orders
     * GET /api/delivery/orders
     * 
     * Query params:
     * - status: Filter by status (pending, confirmed, picked_up, delivered, cancelled)
     * - per_page: Pagination
     */
    public function getOrders(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $perPage = (int) $request->get('per_page', 20);

        $query = DeliveryOrder::with('sale.items.product');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($o) => $this->formatOrder($o)),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ]
        ], 200);
    }

    /**
     * Get order details
     * GET /api/delivery/orders/{id}
     */
    public function getOrderDetails($orderId): JsonResponse
    {
        $order = DeliveryOrder::with(['sale.items.product.category'])->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrderDetailed($order)
        ], 200);
    }

    /**
     * Get customer info from order
     * GET /api/delivery/orders/{id}/customer
     */
    public function getCustomerInfo($orderId): JsonResponse
    {
        $order = DeliveryOrder::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
                'address' => $order->delivery_address,
            ]
        ], 200);
    }

    /**
     * Create delivery order from external system
     * POST /api/delivery/orders
     * 
     * This creates both a DeliveryOrder and Sale record
     */
    public function processOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|unique:delivery_orders',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'delivery_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.branch_id' => 'required|exists:branches,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        try {
            // Validate stock availability
            foreach ($validated['items'] as $item) {
                $stock = ProductBranchStock::where('product_id', $item['product_id'])
                    ->where('branch_id', $item['branch_id'])
                    ->first();

                if (!$stock || $stock->quantity_in_stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for product ID: ' . $item['product_id'],
                        'available' => $stock->quantity_in_stock ?? 0,
                        'requested' => $item['quantity'],
                    ], 400);
                }
            }

            // Create delivery order
            $deliveryOrder = DeliveryOrder::create([
                'order_number' => $validated['order_number'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'delivery_address' => $validated['delivery_address'],
                'total_amount' => $validated['total_amount'],
                'payment_method' => $validated['payment_method'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            // Create sale record
            $sale = Sale::create([
                'receipt_number' => 'DEL-' . $validated['order_number'],
                'cashier_id' => auth()->id() ?? 1, // Use authenticated user or fallback
                'branch_id' => $validated['items'][0]['branch_id'],
                'subtotal' => $validated['total_amount'],
                'total_amount' => $validated['total_amount'],
                'primary_payment_method' => (isset($validated['payment_method']) && in_array($validated['payment_method'], ['cash', 'mpesa', 'card'])) ? $validated['payment_method'] : 'mpesa',
                'status' => 'completed',
                'delivery_status' => 'pending', // Explicit indicator for delivery orders
                'delivery_notes' => 'External System Order: ' . $validated['order_number'],
                'notes' => ($validated['notes'] ?? '') . " | Delivery Order Ref: " . $validated['order_number'],
            ]);

            // Create sale items and reduce inventory
            $itemCount = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'line_total' => $product->selling_price * $item['quantity'],
                ]);

                // Reduce branch stock
                ProductBranchStock::where('product_id', $item['product_id'])
                    ->where('branch_id', $item['branch_id'])
                    ->decrement('quantity_in_stock', $item['quantity']);

                // Reduce main product stock
                $product->decrement('quantity_in_stock', $item['quantity']);

                // Log stock movement
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'branch_id' => $item['branch_id'],
                    'type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'notes' => 'Delivery Order: ' . $validated['order_number'],
                    'user_id' => auth()->id() ?? 1,
                ]);

                $itemCount++;
            }

            // Link delivery order to sale
            $deliveryOrder->update(['sale_id' => $sale->id]);

            return response()->json([
                'success' => true,
                'message' => "Order created with {$itemCount} items",
                'delivery_order' => [
                    'id' => $deliveryOrder->id,
                    'order_number' => $deliveryOrder->order_number,
                    'sale_id' => $sale->id,
                    'status' => $deliveryOrder->status,
                    'total_amount' => (float) $deliveryOrder->total_amount,
                    'item_count' => $itemCount,
                    'created_at' => $deliveryOrder->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update delivery status
     * POST /api/delivery/orders/{id}/status
     */
    public function updateDeliveryStatus(Request $request, $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,picked_up,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        $order = DeliveryOrder::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => $validated['status'],
        ]);

        // Restore inventory if cancelled
        if ($validated['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
            $this->restoreInventory($order);
        }

        return response()->json([
            'success' => true,
            'message' => "Order status updated to {$validated['status']}",
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'updated_at' => $order->updated_at,
            ]
        ], 200);
    }

    /**
     * Update order payment status from external system
     * POST /api/delivery/orders/{orderNumber}/payment
     */
    public function updatePaymentStatus(Request $request, $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:paid,failed',
            'transaction_id' => 'nullable|string',
            'amount' => 'nullable|numeric',
        ]);

        $order = DeliveryOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Delivery Order not found'], 404);
        }

        // Update the associated Sale record
        if ($order->sale) {
            $updateData = [
                'status' => $validated['payment_status'] === 'paid' ? 'completed' : 'pending',
                'notes' => ($order->sale->notes ?? '') . " | Payment Update: " . $validated['payment_status']
            ];

            if ($validated['payment_status'] === 'paid') {
                $updateData['mpesa_paid'] = $validated['amount'] ?? $order->total_amount;
                $updateData['primary_payment_method'] = 'mpesa';
            }

            $order->sale->update($updateData);
        }

        $order->update([
            'payment_method' => $validated['payment_status'] === 'paid' ? 'mpesa' : 'pending',
            'notes' => ($order->notes ?? '') . " | Ext Payment: " . strtoupper($validated['payment_status']) . ($validated['transaction_id'] ? " (TX:{$validated['transaction_id']})" : "")
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status synchronized',
        ]);
    }

    /**
     * Mark order as picked up
     * POST /api/delivery/orders/{id}/picked-up
     */
    public function markPickedUp(Request $request, $orderId): JsonResponse
    {
        return $this->updateDeliveryStatus(
            new Request(['status' => 'picked_up']),
            $orderId
        );
    }

    /**
     * Mark order as delivered
     * POST /api/delivery/orders/{id}/delivered
     */
    public function markDelivered(Request $request, $orderId): JsonResponse
    {
        return $this->updateDeliveryStatus(
            new Request(['status' => 'delivered']),
            $orderId
        );
    }

    /**
     * Mark delivery as failed
     * POST /api/delivery/orders/{id}/failed
     */
    public function markDeliveryFailed(Request $request, $orderId): JsonResponse
    {
        $order = DeliveryOrder::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order->update([
            'status' => 'cancelled',
            'notes' => ($order->notes ? $order->notes . ' | ' : '') . 'Delivery Failed: ' . ($validated['reason'] ?? 'No reason provided'),
        ]);

        // Restore inventory
        $this->restoreInventory($order);

        return response()->json([
            'success' => true,
            'message' => 'Delivery marked as failed, inventory restored',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
            ]
        ], 200);
    }

    /**
     * Get delivery statistics
     * GET /api/delivery/stats
     */
    public function getDeliveryStats(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $fromDate = now()->subDays($days);

        $stats = [
            'total_orders' => DeliveryOrder::where('created_at', '>=', $fromDate)->count(),
            'pending_orders' => DeliveryOrder::where('status', 'pending')
                ->where('created_at', '>=', $fromDate)->count(),
            'delivered_orders' => DeliveryOrder::where('status', 'delivered')
                ->where('created_at', '>=', $fromDate)->count(),
            'cancelled_orders' => DeliveryOrder::where('status', 'cancelled')
                ->where('created_at', '>=', $fromDate)->count(),
            'total_revenue' => DeliveryOrder::where('status', 'delivered')
                ->where('created_at', '>=', $fromDate)->sum('total_amount'),
            'average_order_value' => round(
                DeliveryOrder::where('created_at', '>=', $fromDate)->avg('total_amount'), 
                2
            ),
        ];

        return response()->json([
            'success' => true,
            'period_days' => $days,
            'data' => $stats
        ], 200);
    }

    /**
     * Restore inventory when order is cancelled
     */
    private function restoreInventory(DeliveryOrder $order): void
    {
        if (!$order->sale) return;

        foreach ($order->sale->items as $item) {
            // Restore branch stock
            ProductBranchStock::where('product_id', $item->product_id)
                ->where('branch_id', $order->sale->branch_id)
                ->increment('quantity_in_stock', $item->quantity);

            // Restore main product stock
            $item->product->increment('quantity_in_stock', $item->quantity);

            // Log reversal
            StockMovement::create([
                'product_id' => $item->product_id,
                'branch_id' => $order->sale->branch_id,
                'type' => 'return',
                'quantity' => $item->quantity,
                'notes' => 'Order cancelled/failed: ' . $order->order_number,
                'user_id' => auth()->id() ?? 1,
            ]);
        }
    }

    /**
     * Format product for API response
     */
    private function formatProduct(Product $product): array
    {
        $totalStock = $product->branchStocks->sum('quantity_in_stock');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'category' => $product->category->name ?? 'Uncategorized',
            'selling_price' => (float) $product->selling_price,
            'cost_price' => (float) $product->cost_price,
            'total_stock' => $totalStock,
            'is_low_stock' => $totalStock > 0 && $totalStock <= $product->reorder_level,
            'branches' => $product->branchStocks->map(fn($s) => [
                'branch_id' => $s->branch_id,
                'branch_name' => $s->branch->name,
                'quantity' => $s->quantity_in_stock,
            ])->values(),
        ];
    }

    /**
     * Format order for list view
     */
    private function formatOrder(DeliveryOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'item_count' => $order->sale?->items->count() ?? 0,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format order for detailed view
     */
    private function formatOrderDetailed(DeliveryOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
                'address' => $order->delivery_address,
            ],
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'items' => $order->sale?->items?->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'sku' => $item->product->sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values() ?? [],
            'notes' => $order->notes,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}