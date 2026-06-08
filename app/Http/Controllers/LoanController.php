<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Customer;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = Loan::with(['customer', 'user']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $loans = $query->latest()->paginate(20);

        // Summary statistics
        $stats = [
            'total_active' => Loan::active()->count(),
            'total_outstanding' => Loan::active()->sum(DB::raw('total_amount - amount_paid')),
            'total_overdue' => Loan::overdue()->count(),
            'mtd_collected' => LoanPayment::whereMonth('payment_date', now()->month)->sum('amount'),
        ];

        return view('loans.index', compact('loans', 'stats', 'status'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('loans.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_description' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'loan_date' => 'required|date',
            'due_date' => 'required|date|after:loan_date',
            'initial_payment' => 'nullable|numeric|min:0',
            'payment_method' => 'required_with:initial_payment|in:cash,mpesa,bank_transfer,card,other',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $loan = Loan::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'loan_number' => Loan::generateLoanNumber(),
                'product_description' => $validated['product_description'],
                'total_amount' => $validated['total_amount'],
                'amount_paid' => 0,
                'interest_rate' => $validated['interest_rate'] ?? null,
                'loan_date' => $validated['loan_date'],
                'due_date' => $validated['due_date'],
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Record initial payment if provided
            if (!empty($validated['initial_payment']) && $validated['initial_payment'] > 0) {
                $loan->recordPayment(
                    $validated['initial_payment'],
                    $validated['payment_method'],
                    $validated['reference_number'] ?? null,
                    'Initial payment'
                );
            }

            DB::commit();

            return redirect()->route('loans.show', $loan)
                ->with('success', 'Loan created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create loan: ' . $e->getMessage());
        }
    }

    public function show(Loan $loan)
    {
        $loan->load(['customer', 'user', 'payments.user']);
        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $customers = Customer::orderBy('name')->get();
        return view('loans.edit', compact('loan', 'customers'));
    }

    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_description' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'loan_date' => 'required|date',
            'due_date' => 'required|date|after:loan_date',
            'notes' => 'nullable|string',
        ]);

        $loan->update($validated);

        return redirect()->route('loans.show', $loan)
            ->with('success', 'Loan updated successfully!');
    }

    public function destroy(Loan $loan)
    {
        if ($loan->payments()->count() > 0) {
            return back()->with('error', 'Cannot delete loan with payment history.');
        }

        $loan->delete();

        return redirect()->route('loans.index')
            ->with('success', 'Loan deleted successfully!');
    }

    public function recordPayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $loan->balance,
            'payment_method' => 'required|in:cash,mpesa,bank_transfer,card,other',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $loan->recordPayment(
                $validated['amount'],
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Payment recorded successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
}
