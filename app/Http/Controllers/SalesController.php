<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CartItem;
use App\Models\Branch;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    public function __construct(private SalesService $salesService)
    {}

    public function index(): View
    {
        $sales = Sale::forCurrentUser()
            ->with(['cashier', 'customer'])
            ->latest()
            ->paginate(20);

        return view('sales.index', ['sales' => $sales]);
    }

    public function create(): View
    {
        $activeShift = Shift::where('status', 'open')
            ->where('cashier_id', auth()->id())
            ->first();

        $user = auth()->user();
        
        // Resolve active branch for stock display
        $activeBranchId = $user->branch_id ?: (Branch::where('is_main', true)->first()?->id ?: Branch::first()?->id);
        
        $products = Product::where('is_active', true)
            ->with(['category', 'branchStocks' => function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            }, 'branchStocks.branch'])
            ->get();

        $customers = Customer::all();
        $promotions = \App\Models\Promotion::active()->get();

        return view('sales.pos', [
            'products' => $products,
            'customers' => $customers,
            'promotions' => $promotions,
            'shift' => $activeShift,
            'hasActiveShift' => (bool)$activeShift,
            'activeBranchId' => $activeBranchId
        ]);
    }

    // NEW: Get all products for POS - filtered by branch
    public function getProducts(): JsonResponse
    {
        $user = auth()->user();
        $activeBranchId = $user->branch_id ?: (Branch::where('is_main', true)->first()?->id ?: Branch::first()?->id);
        
        $products = Product::where('is_active', true)
            ->whereHas('branchStocks', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->with(['branchStocks' => function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $branchStock = $product->branchStocks->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'code' => $product->sku, // Mapping SKU to Code for POS
                    'barcode' => $product->barcode,
                    'price' => $product->selling_price,
                    'stock' => $branchStock ? $branchStock->quantity_in_stock : 0
                ];
            });
        
        return response()->json($products);
    }

    // UPDATED: Search products for POS - filtered by branch
    public function searchProduct(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return $this->getProducts();
        }

        $user = auth()->user();
        $activeBranchId = $user->branch_id ?: (Branch::where('is_main', true)->first()?->id ?: Branch::first()?->id);
        
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->whereHas('branchStocks', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->with(['branchStocks' => function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            }])
            ->orderBy('name')
            ->take(20)
            ->get()
            ->map(function ($product) {
                $branchStock = $product->branchStocks->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'code' => $product->sku, // Mapping SKU to Code for POS
                    'barcode' => $product->barcode,
                    'price' => $product->selling_price,
                    'stock' => $branchStock ? $branchStock->quantity_in_stock : 0
                ];
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'cart_data' => 'required|string',
            'payment_method' => 'required|in:cash,mpesa,card,credit',
            'mpesa_phone' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'promotion_id' => 'nullable|exists:promotions,id',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'amount_tendered' => 'nullable|numeric|min:0',
            'trade_in_data' => 'nullable|string',
            'trade_in_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Get active shift
            $shift = Shift::where('status', 'open')
                ->where('cashier_id', auth()->id())
                ->firstOrFail();

            // Parse items from JSON
            $items = json_decode($validated['cart_data'], true);
            if (empty($items)) {
                return back()->with('error', 'No items in sale');
            }

            // Parse trade-ins from JSON
            $tradeIns = [];
            if (!empty($validated['trade_in_data'])) {
                $tradeIns = json_decode($validated['trade_in_data'], true);
            }

            // Convert items to expected format for SalesService
            $formattedItems = [];
            $subtotal = 0;
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['price'];
                $subtotal += $lineTotal;
                $formattedItems[] = [
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_total' => $lineTotal,
                    'discount_per_item' => 0
                ];
            }

            $saleData = [
                'cashier_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'promotion_id' => $validated['promotion_id'] ?? null,
                'tax_amount' => (float)$validated['tax_amount'],
                'discount_amount' => (float)($validated['discount'] ?? 0),
                'trade_in_amount' => (float)($validated['trade_in_amount'] ?? 0),
                'total_amount' => (float)$validated['total_amount'],
                'primary_payment_method' => $validated['payment_method'],
                'cash_paid' => $validated['payment_method'] === 'cash' ? (float)$validated['total_amount'] : 0,
                'mpesa_paid' => $validated['payment_method'] === 'mpesa' ? (float)$validated['total_amount'] : 0,
                'card_paid' => $validated['payment_method'] === 'card' ? (float)$validated['total_amount'] : 0,
                'change_amount' => $validated['payment_method'] === 'cash' ? 
                    max(0, (float)($validated['amount_tendered'] ?? 0) - (float)$validated['total_amount']) : 0,
                'notes' => $validated['mpesa_phone'] ?? null,
                'shift_id' => $shift->id,
                'items' => $formattedItems,
                'trade_ins' => $tradeIns
            ];

            $sale = $this->salesService->createSale($saleData);

            // Clear the database cart for this user
            CartItem::where('user_id', auth()->id())->delete();

            return redirect()->route('sales.receipt', $sale)
                ->with('success', 'Sale completed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating sale: ' . $e->getMessage());
        }
    }

    public function show(Sale $sale): View
    {
        // Ensure user can only view their own sale if they are a cashier
        if (auth()->user()->isCashier() && !auth()->user()->isSuperAdmin() && !auth()->user()->isManager() && $sale->cashier_id != auth()->id()) {
            abort(404);
        }

        return view('sales.show', [
            'sale' => $sale->load(['items.product', 'cashier', 'customer']),
        ]);
    }

    public function receipt(Sale $sale)
    {
        // Ensure user can only view their own sale if they are a cashier
        if (auth()->user()->isCashier() && !auth()->user()->isSuperAdmin() && !auth()->user()->isManager() && $sale->cashier_id != auth()->id()) {
            abort(404);
        }

        return view('sales.receipt', [
            'sale' => $sale->load(['items.product', 'cashier', 'customer']),
        ]);
    }
}