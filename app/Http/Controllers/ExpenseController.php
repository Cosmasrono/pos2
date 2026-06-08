<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function index()
    {
        $expenses = Expense::with(['category', 'recordedBy', 'approvedBy'])
            ->latest('expense_date')
            ->paginate(15);
            
        return view('finance.expenses.index', compact('expenses'));
    }

    public function create()
    {
        $categories = ExpenseCategory::all();
        return view('finance.expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mpesa',
            'reference_number' => 'nullable|string',
        ]);

        $this->financeService->recordExpense($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully and is pending approval.');
    }

    public function show(Expense $expense)
    {
        return view('finance.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        if ($expense->isApproved()) {
            return back()->with('error', 'Approved expenses cannot be edited.');
        }
        
        $categories = ExpenseCategory::all();
        return view('finance.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->isApproved()) {
            return back()->with('error', 'Approved expenses cannot be edited.');
        }

        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mpesa',
            'reference_number' => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->isApproved()) {
            return back()->with('error', 'Approved expenses cannot be deleted.');
        }

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }

    public function approve(Expense $expense)
    {
        $this->financeService->approveExpense($expense);
        return redirect()->route('expenses.index')->with('success', 'Expense approved successfully.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $this->financeService->rejectExpense($expense, $request->rejection_reason);
        return redirect()->route('expenses.index')->with('success', 'Expense rejected successfully.');
    }
}