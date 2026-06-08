<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MetricsService
{
    /**
     * Get today's sales total
     */
    public function getTodaySales(): float
    {
        return Sale::whereDate('created_at', today())
            ->sum('total_amount');
    }

    /**
     * Get month-to-date revenue
     */
    public function getMtdRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');
    }

    /**
     * Get month-to-date cost of goods sold
     */
    public function getMtdCogs(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->sum(DB::raw('sale_items.quantity * ISNULL(products.cost_price, 0)'));
    }

    /**
     * Get month-to-date expenses
     */
    public function getMtdExpenses(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('amount');
    }

    /**
     * Get month-to-date profit
     */
    public function getMtdProfit(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $revenue = $this->getMtdRevenue($startDate, $endDate);
        $cogs = $this->getMtdCogs($startDate, $endDate);
        $expenses = $this->getMtdExpenses($startDate, $endDate);

        return ($revenue - $cogs) - $expenses;
    }

    /**
     * Get count of low stock products
     */
    public function getLowStockCount(): int
    {
        return Product::whereRaw('quantity_in_stock <= reorder_level')
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get sales summary for a date range
     */
    public function getSalesSummary(Carbon $startDate, Carbon $endDate): array
    {
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_sales' => $sales->sum('total_amount'),
            'total_transactions' => $sales->count(),
            'cash_sales' => $sales->sum('cash_paid'),
            'mpesa_sales' => $sales->sum('mpesa_paid'),
            'card_sales' => $sales->sum('card_paid'),
            'average_transaction' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
        ];
    }

    /**
     * Get top selling products for a date range
     */
    public function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10)
    {
        return DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.line_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get expense breakdown for a date range
     */
    public function getExpenseBreakdown(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('expenses.status', 'approved')
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * Get profit & loss statement for a date range
     */
    public function getProfitLoss(Carbon $startDate, Carbon $endDate): array
    {
        $revenue = $this->getMtdRevenue($startDate, $endDate);
        $cogs = $this->getMtdCogs($startDate, $endDate);
        $expenses = $this->getMtdExpenses($startDate, $endDate);
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
            'net_margin' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0,
        ];
    }
}