<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OtherIncome;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function recordExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'expense_date' => 'date',
            'payment_method' => 'in:cash,bank_transfer,cheque,mpesa',
            'reference_number' => 'nullable|string',
        ]);

        $expense = $this->financeService->recordExpense($validated);

        return response()->json($expense, 201);
    }

    public function approveExpense(Expense $expense): JsonResponse
    {
        $this->authorize('approve', $expense);

        $this->financeService->approveExpense($expense);

        return response()->json(['message' => 'Expense approved']);
    }

    public function rejectExpense(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('approve', $expense);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $this->financeService->rejectExpense($expense, $validated['reason']);

        return response()->json(['message' => 'Expense rejected']);
    }

    public function recordOtherIncome(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'income_date' => 'date',
        ]);

        $income = $this->financeService->recordOtherIncome($validated);

        return response()->json($income, 201);
    }

    public function dailyFinancialSummary(Request $request): JsonResponse
    {
        $date = $request->get('date') ? new \DateTime($request->get('date')) : new \DateTime();
        $summary = $this->financeService->getDailyFinancialSummary($date);

        return response()->json($summary);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $pnl = $this->financeService->getProfitAndLoss(
            new \DateTime($validated['start_date']),
            new \DateTime($validated['end_date'])
        );

        return response()->json($pnl);
    }

    public function expenseBreakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $breakdown = $this->financeService->getExpenseBreakdown(
            new \DateTime($validated['start_date']),
            new \DateTime($validated['end_date'])
        );

        return response()->json($breakdown);
    }

    public function monthlyTrend(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $trend = $this->financeService->getMonthlyTrend($months);

        return response()->json($trend);
    }

    public function pendingExpenses(): JsonResponse
    {
        $expenses = Expense::where('status', 'pending')
            ->with('category', 'recordedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($expenses);
    }
}
