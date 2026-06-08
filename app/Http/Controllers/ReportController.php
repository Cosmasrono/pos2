<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $sales = Sale::forCurrentUser()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->with(['cashier', 'items.product'])
            ->latest()
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->exportSalesCsv($sales, $startDate, $endDate);
        }

        $summary = [
            'total_sales' => $sales->sum('total_amount'),
            'total_transactions' => $sales->count(),
            'cash_sales' => $sales->sum('cash_paid'),
            'mpesa_sales' => $sales->sum('mpesa_paid'),
            'card_sales' => $sales->sum('card_paid'),
            'average_transaction' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
        ];

        // Daily Revenue Breakdown
        $dailyRevenue = $sales->groupBy(function($date) {
            return Carbon::parse($date->created_at)->format('Y-m-d');
        })->map(function ($day) {
            return [
                'revenue' => $day->sum('total_amount'),
                'transactions' => $day->count()
            ];
        })->sortKeysDesc();

        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->when(auth()->user()->isCashier() && !auth()->user()->isSuperAdmin() && !auth()->user()->isManager(), function($q) {
                return $q->where('sales.cashier_id', auth()->id());
            })
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_quantity'), DB::raw('SUM(sale_items.line_total) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('reports.sales', compact('summary', 'topProducts', 'sales', 'dailyRevenue', 'startDate', 'endDate'));
    }

    public function profitLoss(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Revenue from sales
        $revenue = Sale::forCurrentUser()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_amount');

        // Cost of goods sold
        $cogs = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->when(auth()->user()->isCashier() && !auth()->user()->isSuperAdmin() && !auth()->user()->isManager(), function($q) {
                return $q->where('sales.cashier_id', auth()->id());
            })
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        // Expenses - Expenses use expense_date (often just format Y-m-d)
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('amount');

        // Pending Expenses (to show as a warning)
        $pendingCount = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'pending')
            ->count();
        $pendingAmount = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'pending')
            ->sum('amount');

        // Expense breakdown - Use COALESCE to handle both manual and predefined categories
        $expenseBreakdown = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('expenses.status', 'approved')
            ->select(
                DB::raw("COALESCE(expenses.category_name, expense_categories.name, 'Uncategorized') as display_name"),
                DB::raw('SUM(expenses.amount) as total_amount')
            )
            ->groupBy(DB::raw("COALESCE(expenses.category_name, expense_categories.name, 'Uncategorized')"))
            ->orderByDesc('total_amount')
            ->get();

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        if ($request->get('export') === 'csv') {
            return $this->exportPnlCsv($revenue, $cogs, $grossProfit, $expenses, $netProfit, $expenseBreakdown, $startDate, $endDate);
        }

        return view('reports.pnl', compact(
            'revenue', 'cogs', 'grossProfit', 'expenses', 'netProfit', 
            'expenseBreakdown', 'startDate', 'endDate', 'pendingCount', 'pendingAmount'
        ));
    }

    protected function exportSalesCsv($sales, $startDate, $endDate)
    {
        $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($sales) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Receipt Number', 'Date', 'Time', 'Cashier', 'Payment Method', 'Items', 'Total Amount']);

            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->receipt_number,
                    $sale->created_at->format('Y-m-d'),
                    $sale->created_at->format('H:i'),
                    $sale->cashier->name,
                    ucfirst($sale->primary_payment_method),
                    $sale->getTotalQuantity(),
                    $sale->total_amount
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportPnlCsv($revenue, $cogs, $grossProfit, $expenses, $netProfit, $expenseBreakdown, $startDate, $endDate)
    {
        $filename = "pnl_report_{$startDate}_to_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($revenue, $cogs, $grossProfit, $expenses, $netProfit, $expenseBreakdown) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Financial Summary']);
            fputcsv($file, ['Total Revenue (Sales)', $revenue]);
            fputcsv($file, ['Cost of Goods Sold (COGS)', $cogs]);
            fputcsv($file, ['Gross Profit', $grossProfit]);
            fputcsv($file, ['Total Operating Expenses', $expenses]);
            fputcsv($file, ['Net Profit', $netProfit]);
            
            fputcsv($file, []); // Empty line
            
            fputcsv($file, ['Expense Breakdown']);
            fputcsv($file, ['Category', 'Amount']);
            foreach ($expenseBreakdown as $item) {
                fputcsv($file, [$item->display_name, $item->total_amount]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
