<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->latest()->paginate(15);
        return view('finance.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('finance.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories'],
            'description' => ['nullable', 'string'],
        ]);

        ExpenseCategory::create($request->all());

        return redirect()->route('expense-categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('finance.categories.edit', compact('expenseCategory'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name,' . $expenseCategory->id],
            'description' => ['nullable', 'string'],
        ]);

        $expenseCategory->update($request->all());

        return redirect()->route('expense-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete category with existing expenses.']);
        }

        $expenseCategory->delete();
        return redirect()->route('expense-categories.index')->with('success', 'Category deleted successfully.');
    }
}
