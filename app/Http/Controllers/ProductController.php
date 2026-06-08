<?php

namespace App\Http\Controllers;
use App\Models\ProductBranchStock;
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
        return view('products.create', ['branches' => $branches]);
    }

public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'name'                    => 'required|string|max:255',
        'sku'                     => 'nullable|unique:products|string|max:100',
        'barcode'                 => 'nullable|unique:products|string|max:100',
        'description'             => 'nullable|string',
        'category_id'             => 'required|string|max:255',
        'cost_price'              => 'nullable|numeric|min:0',
        'selling_price'           => 'required|numeric|min:0',
        'reorder_level'           => 'required|integer|min:0',
        'total_stock'             => 'nullable|integer|min:0',
        'branch_quantities'       => 'nullable|array',
        'branch_quantities.*'     => 'nullable|integer|min:0',
    ]);

    $category = Category::firstOrCreate(['name' => $validated['category_id']]);
    $validated['category_id'] = $category->id;
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
        $product->load('branchStocks');
        return view('products.edit', ['product' => $product, 'branches' => $branches]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|unique:products,sku,' . $product->id . '|string|max:100',
            'barcode' => 'nullable|unique:products,barcode,' . $product->id . '|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'total_initial_stock' => 'required|integer|min:0',
        ]);

        // Find or create category
        $category = Category::firstOrCreate(['name' => $validated['category_id']]);
        $validated['category_id'] = $category->id;
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

        // Log stock movement (assuming StockMovement model)
        if (class_exists('App\Models\StockMovement')) {
            \App\Models\StockMovement::create([
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['branch_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'notes' => ($validated['reference'] ?? 'Manual Stock Addition') . ' - Added by ' . auth()->user()->name,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('products.show', $validated['product_id'])
            ->with('success', "Added {$validated['quantity']} units to branch");
    }
}