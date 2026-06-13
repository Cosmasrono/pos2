<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductBranchStock;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::latest()->get();
        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100|unique:branches,name',
            'code'    => 'nullable|string|max:50|unique:branches,code',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string',
            'is_main' => 'nullable|boolean',
            'stock_distribution_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Set is_main to false if not provided
        $validated['is_main'] = $request->has('is_main') ? true : false;
        // Set owner_id to current user
        $validated['owner_id'] = auth()->id();

        // Only one main branch may exist. Promoting this one demotes any current main.
        DB::transaction(function () use ($validated) {
            if ($validated['is_main']) {
                Branch::where('is_main', true)->update(['is_main' => false]);
            }

            Branch::create($validated);
        });

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch): View
    {
        $branch->load('productBranchStocks');
        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100|unique:branches,name,' . $branch->id,
            'code'    => 'nullable|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string',
            'phone'   => 'nullable|string',
            'is_main' => 'nullable|boolean',
            'stock_distribution_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Set is_main to false if not provided
        $validated['is_main'] = $request->has('is_main') ? true : false;

        // There must always be exactly one main branch. Block un-setting the main
        // unless another branch will take over (otherwise the whole system loses
        // its default branch — which is what wiped the data previously).
        if ($branch->is_main && ! $validated['is_main']) {
            $anotherMainExists = Branch::where('is_main', true)->where('id', '!=', $branch->id)->exists();
            if (! $anotherMainExists) {
                return redirect()->route('branches.index')
                    ->with('error', 'You cannot remove the main flag from the only main branch. Mark another branch as main first.');
            }
        }

        // Only one main branch may exist. Promoting this one demotes any current main.
        DB::transaction(function () use ($validated, $branch) {
            if ($validated['is_main']) {
                Branch::where('is_main', true)->where('id', '!=', $branch->id)->update(['is_main' => false]);
            }

            $branch->update($validated);
        });

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }
public function destroy(Branch $branch): RedirectResponse
{
    if ((bool) $branch->is_main) {
        return redirect()->route('branches.index')
            ->with('error', 'The main branch cannot be deleted.');
    }

    $mainBranch = Branch::where('is_main', true)->first();

    if (! $mainBranch) {
        return redirect()->route('branches.index')
            ->with('error', 'No main branch found to reassign products to.');
    }

    $blockers = [
        'users'    => $branch->users()->count(),
        'sales'    => $branch->sales()->count(),
        'shifts'   => $branch->shifts()->count(),
        'expenses' => $branch->expenses()->count(),
    ];

    $inUse = array_filter($blockers);

    if (! empty($inUse)) {
        $details = collect($inUse)
            ->map(fn ($count, $type) => "{$count} {$type}")
            ->implode(', ');

        return redirect()->route('branches.index')
            ->with('error', "This branch cannot be deleted because it still has: {$details}. Reassign or remove them first.");
    }

    DB::beginTransaction();

    try {
        // Move this branch's stock into the main branch BEFORE deleting.
        // The product_branch_stocks FK cascades on delete, so without this
        // the stock would be lost. We merge quantities into the main branch.
        foreach ($branch->productStocks as $stock) {
            $mainStock = ProductBranchStock::firstOrCreate(
                ['product_id' => $stock->product_id, 'branch_id' => $mainBranch->id],
                ['quantity_in_stock' => 0, 'initial_allocation' => 0]
            );
            $mainStock->increment('quantity_in_stock', $stock->quantity_in_stock);
            $mainStock->increment('initial_allocation', $stock->quantity_in_stock);
        }

        // Deleting the branch cascade-removes its (now-merged) stock rows.
        $branch->delete();

        DB::commit();
    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack();

        // Catch any remaining foreign-key reference (e.g. stock movements,
        // purchase orders) so the user sees a message instead of a 500.
        return redirect()->route('branches.index')
            ->with('error', 'This branch is still linked to other records and cannot be deleted yet.');
    }

    return redirect()->route('branches.index')
        ->with('success', 'Branch deleted successfully. Its stock was moved to the main branch.');
}
}

