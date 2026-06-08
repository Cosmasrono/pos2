<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockMovement::where('type', 'transfer')
            ->with(['product', 'branch', 'fromBranch'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('transfers.index', compact('transfers'));
    }
 

    public function create()
{
    $user = auth()->user();
    $branches = Branch::where('is_active', true);

    if ($user->branch_id) {
        $branches->where('id', '!=', $user->branch_id);
    }

    $branches    = $branches->get();
    $allBranches = Branch::where('is_active', true)->get();
    $products    = Product::where('is_active', true)->get();

    return view('transfers.create', compact('branches', 'products', 'allBranches', 'user'));
}


    public function getStockInfo(Request $request)
{
    $request->validate([
        'product_id'       => 'required|exists:products,id',
        'source_branch_id' => 'required|exists:branches,id',
    ]);

    $stock = ProductBranchStock::where('product_id', $request->product_id)
        ->where('branch_id', $request->source_branch_id)
        ->first();

    if (!$stock) {
        return response()->json(['available' => false]);
    }

    $baseline   = $stock->initial_allocation;
    $threshold  = 0.75 * $baseline;
    $maxFetch   = floor(0.75 * $baseline);
    $eligible   = $stock->quantity_in_stock >= $threshold;

    return response()->json([
        'available'        => true,
        'quantity_in_stock' => $stock->quantity_in_stock,
        'initial_allocation'=> $baseline,
        'threshold'        => $threshold,
        'max_fetch'        => $maxFetch,
        'eligible'         => $eligible,
    ]);
}

    public function store(Request $request)
    {
      $validated = $request->validate([
    'product_id'       => 'required|exists:products,id',
    'source_branch_id' => 'required|exists:branches,id',
    'quantity'         => 'required|integer|min:1',
    'target_branch_id' => 'nullable|exists:branches,id',
]);

        $user = auth()->user();
$targetBranchId = $user->branch_id ?? $validated['target_branch_id'] ?? null;

if (!$targetBranchId) {
    return back()->with('error', 'Please select a destination branch.');
}

        if ($validated['source_branch_id'] == $targetBranchId) {
            return back()->with('error', 'Source and destination branches cannot be the same.');
        }

        return DB::transaction(function () use ($validated, $targetBranchId, $user) {
            $product = Product::findOrFail($validated['product_id']);
            $sourceStock = ProductBranchStock::where('product_id', $product->id)
                ->where('branch_id', $validated['source_branch_id'])
                ->first();

            if (!$sourceStock) {
                return back()->with('error', 'The source branch does not have this product in stock.');
            }

            $sourceBranch = Branch::find($validated['source_branch_id']);
            $baseline = $sourceStock->initial_allocation;
            $threshold = 0.75 * $baseline;

            // 1. Check Eligibility (Rule: Remaining >= 3/4 of total allocation)
            if ($sourceStock->quantity_in_stock < $threshold) {
                return back()->with('error', "Cannot fetch from {$sourceBranch->name}. It only has {$sourceStock->quantity_in_stock} remaining, which is below the 75% threshold (" . number_format($threshold, 1) . ") of its allocation.");
            }

            // 2. Check Limit (Rule: Fetch not more than 3/4 items)
            $maxFetch = 0.75 * $baseline;
            if ($validated['quantity'] > $maxFetch) {
                return back()->with('error', "Maximum fetch allowed from this branch is " . number_format($maxFetch, 0) . " units (75% of its total allocation).");
            }

            if ($sourceStock->quantity_in_stock < $validated['quantity']) {
                return back()->with('error', "The source branch only has {$sourceStock->quantity_in_stock} units available.");
            }

            // Execution
            $sourceStock->decrement('quantity_in_stock', $validated['quantity']);

            $targetStock = ProductBranchStock::firstOrCreate(
                ['product_id' => $product->id, 'branch_id' => $targetBranchId],
                ['quantity_in_stock' => 0, 'initial_allocation' => 0]
            );
            $targetStock->increment('quantity_in_stock', $validated['quantity']);
            // We increment initial_allocation too because it's a "new" allocation for this branch
            $targetStock->increment('initial_allocation', $validated['quantity']);

            // Record Movements
 // Record Movements
StockMovement::create([
    'product_id' => $product->id,
    'branch_id'  => $validated['source_branch_id'],
    'type'       => 'transfer',              // ← was 'out', not allowed by DB constraint
    'quantity'   => $validated['quantity'],
    'notes'      => "Transferred to " . Branch::find($targetBranchId)->name . " (Rule 3/4 validated)",
    'user_id'    => $user->id,
]);

StockMovement::create([
    'product_id'     => $product->id,
    'branch_id'      => $targetBranchId,
    'type'           => 'transfer',
    'from_branch_id' => $validated['source_branch_id'],
    'quantity'       => $validated['quantity'],
    'notes'          => "Fetched from " . $sourceBranch->name,
    'user_id'        => $user->id,
]);

            return redirect()->route('products.index')->with('success', "Successfully fetched {$validated['quantity']} units from {$sourceBranch->name}.");
        });
    }
}
