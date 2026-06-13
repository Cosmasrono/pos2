<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\ProductBranchStock;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with(['supplier', 'createdBy'])
            ->latest()
            ->paginate(15);
        
        // Summary data for restaurant manager
        $pendingCount = PurchaseOrder::where('status', 'pending')->count();
        
        $receivedCount = PurchaseOrder::where('status', 'received')
            ->whereYear('received_date', now()->year)
            ->whereMonth('received_date', now()->month)
            ->count();
        
        $totalSpending = PurchaseOrder::where('status', 'received')
            ->whereYear('received_date', now()->year)
            ->whereMonth('received_date', now()->month)
            ->sum('total_cost');
        
        return view('purchases.orders.index-simple', compact('orders', 'pendingCount', 'receivedCount', 'totalSpending'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        
        return view('purchases.orders.create-simple', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.cost' => ['required', 'numeric', 'min:0'],
        ]);

        // Generate PO number
        $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);

        $order = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $request->supplier_id,
            'supplier_name' => $request->supplier_name,
            'order_date' => $request->order_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Add items
        $totalCost = 0;
        foreach ($request->products as $item) {
            $order->items()->create([
                'product_id' => $item['id'],
                'quantity_ordered' => $item['quantity'],
                'unit_cost' => $item['cost'],
            ]);
            $totalCost += $item['quantity'] * $item['cost'];
        }

        $order->update(['total_cost' => $totalCost]);

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'createdBy', 'receivedBy']);
        return view('purchases.orders.show', compact('purchaseOrder'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->withErrors(['error' => 'Order already received.']);
        }

        $user = auth()->user();
        $branchId = $user->branch_id ?? optional(Branch::where('is_main', true)->first())->id ?? Branch::first()?->id;

        // Update inventory for each item
        foreach ($purchaseOrder->items as $item) {
            $product = $item->product;
            $qty = $item->quantity_ordered;

            // Update branch stock record
            $branchStock = ProductBranchStock::firstOrCreate(
                ['product_id' => $product->id, 'branch_id' => $branchId],
                ['quantity_in_stock' => 0, 'initial_allocation' => 0]
            );
            $branchStock->increment('quantity_in_stock', $qty);
            $branchStock->increment('initial_allocation', $qty);

            // Keep the master quantity in sync
            $product->quantity_in_stock = $product->branchStocks()->sum('quantity_in_stock');
            $product->save();

            if (class_exists('App\Models\StockMovement')) {
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'branch_id'  => $branchId,
                    'type'       => 'purchase',
                    'quantity'   => $qty,
                    'notes'      => 'PO ' . $purchaseOrder->po_number . ' received',
                    'user_id'    => auth()->id(),
                ]);
            }

            $item->update(['quantity_received' => $qty]);
        }

        $purchaseOrder->update([
            'status' => 'received',
            'received_date' => now(),
            'received_by' => auth()->id(),
        ]);

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order received and inventory updated.');
    }
}
