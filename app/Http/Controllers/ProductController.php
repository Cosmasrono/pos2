<?php

namespace App\Http\Controllers;
use App\Models\ProductBranchStock;
use App\Models\ProductBatch;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $filter = $request->get('filter', 'active'); // all, active, inactive
        
        // Build base query - always load all products with their branch stocks
        $query = Product::with('category', 'branchStocks.branch');
        
        // If user is a branch user (not admin/main), filter to show only their branch stocks
        if ($user && $user->branch_id) {
            $query->with(['branchStocks' => function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            }]);
        }

        // Apply status filter
        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        }
        // 'all' shows both active and inactive

        $products = $query->paginate(15)->appends(['filter' => $filter]);

        return view('products.index', [
            'products' => $products,
            'filter' => $filter
        ]);
    }

    public function create(): View
    {
        $branches = Branch::orderByDesc('is_main')->get();
        $categories = Category::orderBy('name')->get();
        return view('products.create', ['branches' => $branches, 'categories' => $categories]);
    }

public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'name'                    => 'required|string|max:255',
        'sku'                     => 'nullable|unique:products|string|max:100',
        'barcode'                 => 'nullable|unique:products|string|max:100',
        'description'             => 'nullable|string',
        'category_id'             => 'required|exists:categories,id',
        'cost_price'              => 'nullable|numeric|min:0',
        'selling_price'           => 'required|numeric|min:0',
        'reorder_level'           => 'required|integer|min:0',
        'total_stock'             => 'nullable|integer|min:0',
        'branch_quantities'       => 'nullable|array',
        'branch_quantities.*'     => 'nullable|integer|min:0',
        'expiry_date'             => 'nullable|date',
    ]);

    // Pull expiry out — it belongs on the stock batch, not the products table.
    $expiryDate = $validated['expiry_date'] ?? null;
    unset($validated['expiry_date']);

    $validated['cost_price'] ??= 0;

    // Auto-generate SKU based on timestamp: ddmmyy-hhmmss-microseconds
    if (empty($validated['sku'])) {
        $now = now();
        $validated['sku'] = $now->format('dmy-His') . '-' . substr(microtime(false), 2, 6);
    }

    $user = auth()->user();
    $totalStock = (int) ($request->input('total_stock', 0));
    $branchQuantities = collect($request->input('branch_quantities', []))
        ->map(fn($q) => max(0, (int) $q));

    if (!$user->branch_id) {
        // SuperAdmin: Enforce total_stock and handle Main Branch remainder
        $mainBranch = Branch::where('is_main', true)->first() ?? Branch::first();
        
        if ($mainBranch) {
            $otherAllocations = $branchQuantities->except($mainBranch->id)->sum();
            // Main Branch gets the remaining stock from total_stock
            $branchQuantities[$mainBranch->id] = max(0, $totalStock - $otherAllocations);
        }
        
        $validated['quantity_in_stock'] = $totalStock;
    } else {
        // Branch user: only their branch is relevant
        $qty = $branchQuantities->get($user->branch_id, 0);
        $totalStock = $qty;
        $validated['quantity_in_stock'] = $qty;
        $branchQuantities = collect([$user->branch_id => $qty]);
    }

    $product = Product::create($validated);

    // Record stock for each branch
    foreach ($branchQuantities as $branchId => $qty) {
        if ($qty > 0) {
            ProductBranchStock::create([
                'product_id'         => $product->id,
                'branch_id'          => (int) $branchId,
                'quantity_in_stock'  => $qty,
                'initial_allocation' => $qty,
            ]);

            // Seed the expiry ledger with an initial batch (expiry optional).
            \App\Models\ProductBatch::create([
                'product_id'   => $product->id,
                'branch_id'    => (int) $branchId,
                'batch_number' => 'INITIAL',
                'expiry_date'  => $expiryDate,
                'quantity'     => $qty,
                'cost_price'   => $product->cost_price,
                'received_at'  => now(),
            ]);
        }
    }

    return redirect()->route('products.index')
        ->with('success', 'Product created successfully with ' . number_format($totalStock) . ' total units allocated.');
}


    public function show(Product $product): View
    {
        $product->load('category', 'branchStocks.branch', 'stockMovements');
        return view('products.show', ['product' => $product]);
    }

    public function edit(Product $product): View
    {
        $branches = Branch::all();
        $categories = Category::orderBy('name')->get();
        $product->load('branchStocks');
        return view('products.edit', ['product' => $product, 'branches' => $branches, 'categories' => $categories]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|unique:products,sku,' . $product->id . '|string|max:100',
            'barcode' => 'nullable|unique:products,barcode,' . $product->id . '|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'total_initial_stock' => 'required|integer|min:0',
        ]);

        $validated['cost_price'] ??= 0;
        $validated['quantity_in_stock'] = $validated['total_initial_stock'];

        $product->update($validated);

        $user = auth()->user();
        $totalStock = $validated['total_initial_stock'];

        // If user is SuperAdmin (main account), redistribute to all branches
        if (!$user->branch_id) {
            $product->branchStocks()->delete();
            
            $branches = Branch::where('is_active', true)->get();
            $allocatedTotal = 0;

            foreach ($branches as $branch) {
                $percentage = $branch->stock_distribution_percentage ?? 0;
                
                if ($percentage > 0) {
                    $qty = (int) round(($totalStock * $percentage) / 100);
                    
                    if ($qty > 0) {
                        ProductBranchStock::create([
                            'product_id'        => $product->id,
                            'branch_id'         => $branch->id,
                            'quantity_in_stock' => $qty,
                            'initial_allocation' => $qty,
                        ]);
                        $allocatedTotal += $qty;
                    }
                }
            }

            // If no branches had percentages, distribute equally
            if ($allocatedTotal === 0 && $branches->isNotEmpty()) {
                $qtyPerBranch = (int) floor($totalStock / $branches->count());
                
                foreach ($branches as $branch) {
                    if ($qtyPerBranch > 0) {
                        ProductBranchStock::create([
                            'product_id'        => $product->id,
                            'branch_id'         => $branch->id,
                            'quantity_in_stock' => $qtyPerBranch,
                            'initial_allocation' => $qtyPerBranch,
                        ]);
                    }
                }
            }
        } else {
            // If user is a branch user, update only their branch's stock
            $branchStock = $product->branchStocks()->where('branch_id', $user->branch_id)->first();
            
            if ($branchStock) {
                $branchStock->update([
                    'quantity_in_stock' => $totalStock,
                    'initial_allocation' => $totalStock,
                ]);
            } else {
                // Create if doesn't exist
                ProductBranchStock::create([
                    'product_id'        => $product->id,
                    'branch_id'         => $user->branch_id,
                    'quantity_in_stock' => $totalStock,
                    'initial_allocation' => $totalStock,
                ]);
            }
        }

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);
        return redirect()->route('products.index')
            ->with('success', 'Product deactivated successfully');
    }

    /**
     * Expiry tracking dashboard — expired stock and batches expiring soon.
     */
    public function expiryReport(Request $request): View
    {
        $user      = auth()->user();
        $window    = (int) $request->get('window', 90);   // "expiring soon" horizon (days)
        $today     = now()->startOfDay();

        $base = ProductBatch::query()
            ->with(['product:id,name,sku', 'branch:id,name'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date');

        // Branch users only see their own branch.
        if ($user->branch_id) {
            $base->where('branch_id', $user->branch_id);
        }

        $expired = (clone $base)
            ->whereDate('expiry_date', '<', $today->toDateString())
            ->orderBy('expiry_date')
            ->get();

        $expiringSoon = (clone $base)
            ->whereDate('expiry_date', '>=', $today->toDateString())
            ->whereDate('expiry_date', '<=', $today->copy()->addDays($window)->toDateString())
            ->orderBy('expiry_date')
            ->get();

        $valueAtRisk = $expired->sum(fn ($b) => $b->quantity * (float) ($b->cost_price ?? 0));

        return view('products.expiry', [
            'expired'          => $expired,
            'expiringSoon'     => $expiringSoon,
            'window'           => $window,
            'valueAtRisk'      => $valueAtRisk,
            'expiredUnits'     => $expired->sum('quantity'),
            'expiringUnits'    => $expiringSoon->sum('quantity'),
        ]);
    }

    public function addStock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id', // Now requires branch
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
        ]);

        $stock = ProductBranchStock::firstOrCreate(
            ['product_id' => $validated['product_id'], 'branch_id' => $validated['branch_id']],
            ['quantity_in_stock' => 0]
        );

        $stock->increment('quantity_in_stock', $validated['quantity']);
        $stock->increment('initial_allocation', $validated['quantity']);

        // Track as a batch so the expiry ledger stays in sync (expiry unknown here).
        \App\Models\ProductBatch::create([
            'product_id'   => $validated['product_id'],
            'branch_id'    => $validated['branch_id'],
            'batch_number' => $validated['reference'] ?? 'MANUAL',
            'expiry_date'  => null,
            'quantity'     => $validated['quantity'],
            'cost_price'   => optional(Product::find($validated['product_id']))->cost_price,
            'received_at'  => now(),
        ]);

        // Log stock movement (assuming StockMovement model)
        if (class_exists('App\Models\StockMovement')) {
            \App\Models\StockMovement::create([
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['branch_id'],
                'type' => 'restock',
                'quantity' => $validated['quantity'],
                'notes' => ($validated['reference'] ?? 'Manual Stock Addition') . ' - Added by ' . auth()->user()->name,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->back()
            ->with('success', "Added {$validated['quantity']} units to stock successfully.");
    }

    public function receiveDelivery(): View
    {
        $user = auth()->user();
        $query = Product::with(['category', 'branchStocks'])->where('is_active', true)->orderBy('name');

        $products = $query->get();
        $branches = \App\Models\Branch::where('is_active', true)->get();

        $defaultBranchId = $user->branch_id ?? optional(\App\Models\Branch::where('is_main', true)->first())->id;

        return view('products.receive-delivery', compact('products', 'branches', 'defaultBranchId'));
    }

    public function processDelivery(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id'       => 'required|exists:branches,id',
            'reference'       => 'nullable|string|max:255',
            'quantities'      => 'required|array',
            'quantities.*'    => 'nullable|integer|min:0',
            'batch_number'    => 'nullable|array',
            'batch_number.*'  => 'nullable|string|max:100',
            'expiry_date'     => 'nullable|array',
            'expiry_date.*'   => 'nullable|date',
        ]);

        $branchId   = $request->branch_id;
        $reference  = $request->reference ?? 'Delivery received';
        $userName   = auth()->user()->name;
        $batchInput = $request->input('batch_number', []);
        $expiryInput= $request->input('expiry_date', []);
        $updated    = 0;

        foreach ($request->quantities as $productId => $qty) {
            if (!$qty || $qty <= 0) continue;

            $product = Product::find($productId);
            if (!$product) continue;

            $stock = ProductBranchStock::firstOrCreate(
                ['product_id' => $productId, 'branch_id' => $branchId],
                ['quantity_in_stock' => 0, 'initial_allocation' => 0]
            );
            $stock->increment('quantity_in_stock', $qty);
            $stock->increment('initial_allocation', $qty);

            // Track this delivery as a batch (expiry ledger).
            \App\Models\ProductBatch::create([
                'product_id'   => $productId,
                'branch_id'    => $branchId,
                'batch_number' => $batchInput[$productId] ?? null,
                'expiry_date'  => $expiryInput[$productId] ?? null,
                'quantity'     => $qty,
                'cost_price'   => $product->cost_price,
                'received_at'  => now(),
            ]);

            // Update master quantity
            $product->quantity_in_stock = $product->branchStocks()->sum('quantity_in_stock');
            $product->save();

            if (class_exists('App\Models\StockMovement')) {
                \App\Models\StockMovement::create([
                    'product_id' => $productId,
                    'branch_id'  => $branchId,
                    'type'       => 'restock',
                    'quantity'   => $qty,
                    'notes'      => $reference . ' - Received by ' . $userName,
                    'user_id'    => auth()->id(),
                ]);
            }
            $updated++;
        }

        return redirect()->route('products.index')
            ->with('success', "Delivery received: {$updated} product(s) restocked successfully.");
    }
}