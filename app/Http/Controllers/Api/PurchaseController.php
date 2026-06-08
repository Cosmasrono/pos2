<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function suppliers(): JsonResponse
    {
        $suppliers = Supplier::where('is_active', true)
            ->paginate(20);

        return response()->json($suppliers);
    }

    public function createSupplier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|unique:suppliers',
            'contact_person' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function createPurchaseOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ]);

        $po = $this->purchaseService->createPurchaseOrder($validated);

        return response()->json($po, 201);
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $po): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.quantity_damaged' => 'nullable|integer|min:0',
        ]);

        $this->purchaseService->receiveStock($po, $validated['items']);

        return response()->json(['message' => 'Stock received successfully']);
    }

    public function recordPayment(Request $request, PurchaseOrder $po): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:full,partial,advance',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mpesa',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['payment_date'] = now()->date();

        $payment = $this->purchaseService->recordPayment($po, $validated);

        return response()->json($payment, 201);
    }

    public function purchaseOrders(): JsonResponse
    {
        $pos = PurchaseOrder::with('supplier', 'items')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($pos);
    }

    public function pendingOrders(): JsonResponse
    {
        $orders = $this->purchaseService->getPendingOrders();
        return response()->json($orders);
    }

    public function supplierBalance(Supplier $supplier): JsonResponse
    {
        $balance = $this->purchaseService->getSupplierBalance($supplier);
        return response()->json($balance);
    }

    public function purchaseOrderDetail(PurchaseOrder $po): JsonResponse
    {
        $po->load('supplier', 'items.product', 'createdBy', 'payments');
        return response()->json($po);
    }
}
