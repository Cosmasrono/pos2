<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\OtherIncome;

class FinanceService
{
    public function recordExpense(array $data): Expense
    {
        $branchId = auth()->user()?->branch_id;
        if (!$branchId) {
            $branchId = \App\Models\Branch::where('is_main', true)->value('id');
        }

        return Expense::create([
            'category_name' => $data['category_name'],
            'category_id' => $data['category_id'] ?? null,
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'] ?? now()->date(),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference_number' => $data['reference_number'] ?? null,
            'status' => 'pending',
            'recorded_by' => auth()->id(),
            'branch_id' => $branchId,
        ]);
    }

    public function approveExpense(Expense $expense): void
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function rejectExpense(Expense $expense, string $reason): void
    {
        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function recordOtherIncome(array $data): OtherIncome
    {
        return OtherIncome::create([
            'source' => $data['source'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'income_date' => $data['income_date'] ?? now()->date(),
            'recorded_by' => auth()->id(),
        ]);
    }

    public function getDailyFinancialSummary(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');

        // Sales income
        $sales = \App\Models\Sale::whereDate('created_at', $dateStr)
            ->where('status', 'completed')
            ->selectRaw('SUM(total_amount) as total_sales, COUNT(*) as transaction_count')
            ->first();

        // Other income
        $otherIncome = OtherIncome::whereDate('income_date', $dateStr)
            ->sum('amount');

        // Expenses (approved only)
        $expenses = Expense::whereDate('expense_date', $dateStr)
            ->where('status', 'approved')
            ->selectRaw('SUM(amount) as total_expenses')
            ->first();

        $totalIncome = ($sales->total_sales ?? 0) + $otherIncome;
        $totalExpenses = $expenses->total_expenses ?? 0;

        return [
            'total_sales' => $sales->total_sales ?? 0,
            'sales_transactions' => $sales->transaction_count ?? 0,
            'other_income' => $otherIncome,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
            'profit_margin' => $totalIncome > 0 ? (($totalIncome - $totalExpenses) / $totalIncome) * 100 : 0,
        ];
    }

    public function getProfitAndLoss(\DateTime $startDate, \DateTime $endDate): array
    {
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        // Total sales revenue
        $sales = \App\Models\Sale::whereBetween('created_at', [$startStr, $endStr])
            ->where('status', 'completed')
            ->sum('total_amount');

        // Cost of goods sold
        $cogs = \App\Models\SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startStr, $endStr])
            ->sum(\DB::raw('sale_items.quantity * products.cost_price'));

        // Other income
        $otherIncome = OtherIncome::whereBetween('income_date', [$startStr, $endStr])
            ->sum('amount');

        // Expenses (approved only)
        $expenses = Expense::whereBetween('expense_date', [$startStr, $endStr])
            ->where('status', 'approved')
            ->sum('amount');

        $totalIncome = $sales + $otherIncome;
        $grossProfit = $sales - $cogs;
        $netProfit = $totalIncome - $expenses;

        return [
            'total_revenue' => $sales,
            'cost_of_goods_sold' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $sales > 0 ? ($grossProfit / $sales) * 100 : 0,
            'other_income' => $otherIncome,
            'total_income' => $totalIncome,
            'total_expenses' => $expenses,
            'net_profit' => $netProfit,
            'net_profit_margin' => $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0,
        ];
    }

    public function getExpenseBreakdown(\DateTime $startDate, \DateTime $endDate): array
    {
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        return \App\Models\ExpenseCategory::with([
            'expenses' => function ($query) use ($startStr, $endStr) {
                $query->whereBetween('expense_date', [$startStr, $endStr])
                      ->where('status', 'approved');
            }
        ])
            ->get()
            ->map(function ($category) {
                return [
                    'category' => $category->name,
                    'total' => $category->expenses->sum('amount'),
                    'count' => $category->expenses->count(),
                ];
            })
            ->toArray();
    }

    public function getMonthlyTrend(int $months = 12): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            $pnl = $this->getProfitAndLoss($startDate, $endDate);

            $data[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $pnl['total_revenue'],
                'expenses' => $pnl['total_expenses'],
                'profit' => $pnl['net_profit'],
            ];
        }

        return $data;
    }
}
