<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\User;
use App\Models\Shift;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        
        // Get today's sales - only user's own sales
        $todaySales = Sale::where('cashier_id', $user->id)
            ->whereDate('created_at', today())
            ->sum('total_amount');

        // Get total products
        $totalProducts = Product::where('is_active', true)->count();

        // Get low stock products
        $lowStockProducts = Product::whereRaw('quantity_in_stock <= reorder_level')
            ->where('is_active', true)
            ->count();

        // Get active shift
        $activeShift = Shift::where('status', 'open')
            ->where('cashier_id', $user->id)
            ->first();

        // Get recent sales - only user's own sales
        $recentSales = Sale::where('cashier_id', $user->id)
            ->latest()
            ->take(10)
            ->with(['cashier', 'customer', 'branch'])
            ->get();

        // Get branch-wise sales for SuperAdmin
        $branchSales = null;
        if ($user->isSuperAdmin()) {
            $branchSales = \App\Models\Branch::with([
                'sales' => function($q) {
                    $q->whereDate('created_at', today())
                      ->where('status', 'completed');
                }
            ])
            ->where('is_active', true)
            ->get()
            ->map(function($branch) {
                return [
                    'name' => $branch->name,
                    'sales_count' => $branch->sales->count(),
                    'total_amount' => $branch->sales->sum('total_amount'),
                ];
            });
        }

        // Month-to-date Profit & Loss details - only user's own sales
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $mtdRevenue = Sale::where('cashier_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->sum('total_amount');

        $mtdCogs = \DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.cashier_id', $user->id)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->where('sales.status', 'completed')
            ->sum(\DB::raw('sale_items.quantity * products.cost_price'));

        $mtdExpenses = \App\Models\Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('amount');

        $mtdProfit = ($mtdRevenue - $mtdCogs) - $mtdExpenses;

        $isSystemActive = \App\Models\Setting::isSystemActive();
        $subscriptionStatus = \App\Models\Setting::get('subscription_status', 'active');
        $subscriptionExpiresAt = \App\Models\Setting::getSubscriptionExpiryDate();

        return view('dashboard.index', [
            'todaySales' => $todaySales,
            'totalProducts' => $totalProducts,
            'lowStockProducts' => $lowStockProducts,
            'activeShift' => $activeShift,
            'recentSales' => $recentSales,
            'mtdProfit' => $mtdProfit,
            'mtdRevenue' => $mtdRevenue,
            'branchSales' => $branchSales,
            'isSystemActive' => $isSystemActive,
            'subscriptionStatus' => $subscriptionStatus,
            'subscriptionExpiresAt' => $subscriptionExpiresAt,
        ]);
    }

    public function superAdminInventory(): View
    {
        // ── KPI Totals ──────────────────────────────────────────────────
        $totalActiveProducts = Product::where('is_active', true)->count();
        $totalStockUnits     = ProductBranchStock::sum('quantity_in_stock');

        $inventoryCostValue    = ProductBranchStock::join('products', 'product_branch_stocks.product_id', '=', 'products.id')
            ->sum(\DB::raw('product_branch_stocks.quantity_in_stock * products.cost_price'));

        $inventorySellingValue = ProductBranchStock::join('products', 'product_branch_stocks.product_id', '=', 'products.id')
            ->sum(\DB::raw('product_branch_stocks.quantity_in_stock * products.selling_price'));

        // Low-stock: products where any branch stock <= reorder_level
        $lowStockItems = ProductBranchStock::with(['product', 'branch'])
            ->join('products', 'product_branch_stocks.product_id', '=', 'products.id')
            ->whereColumn('product_branch_stocks.quantity_in_stock', '<=', 'products.reorder_level')
            ->where('products.is_active', true)
            ->select('product_branch_stocks.*')
            ->orderBy('product_branch_stocks.quantity_in_stock', 'asc')
            ->get();

        // ── Per-Branch Stock Summary ─────────────────────────────────────
        $branches = Branch::where('is_active', true)
            ->with(['productStocks.product'])
            ->get()
            ->map(function ($branch) use ($totalStockUnits) {
                $stocks       = $branch->productStocks;
                $units        = $stocks->sum('quantity_in_stock');
                $costVal      = $stocks->sum(fn($s) => $s->quantity_in_stock * ($s->product->cost_price ?? 0));
                $sellingVal   = $stocks->sum(fn($s) => $s->quantity_in_stock * ($s->product->selling_price ?? 0));
                $pct          = $totalStockUnits > 0 ? round(($units / $totalStockUnits) * 100, 1) : 0;

                return [
                    'id'            => $branch->id,
                    'name'          => $branch->name,
                    'product_count' => $stocks->count(),
                    'total_units'   => $units,
                    'cost_value'    => $costVal,
                    'selling_value' => $sellingVal,
                    'pct_of_total'  => $pct,
                ];
            });

        // ── Recent Stock Movements ───────────────────────────────────────
        $recentMovements = StockMovement::with(['product', 'user'])
            ->join('branches', 'stock_movements.branch_id', '=', 'branches.id')
            ->select('stock_movements.*', 'branches.name as branch_name')
            ->latest('stock_movements.created_at')
            ->limit(20)
            ->get();

        // ── Today's Branch Sales ─────────────────────────────────────────
        $branchSalesToday = Branch::where('is_active', true)
            ->with(['sales' => fn($q) => $q->whereDate('created_at', today())->where('status', 'completed')])
            ->get()
            ->map(fn($b) => [
                'name'         => $b->name,
                'sales_count'  => $b->sales->count(),
                'total_amount' => $b->sales->sum('total_amount'),
            ]);

        return view('dashboard.superadmin-inventory', compact(
            'totalActiveProducts',
            'totalStockUnits',
            'inventoryCostValue',
            'inventorySellingValue',
            'lowStockItems',
            'branches',
            'recentMovements',
            'branchSalesToday'
        ));
    }
}
