<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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

        if ($validated['is_main']) {
            Branch::where('is_main', true)->update(['is_main' => false]);
        }

        Branch::create($validated);

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

        if ($validated['is_main']) {
            Branch::where('is_main', true)->where('id', '!=', $branch->id)->update(['is_main' => false]);
        }

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}

