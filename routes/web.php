<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SystemControlController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AIInventoryController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/superadmin/inventory', [App\Http\Controllers\DashboardController::class, 'superAdminInventory'])->name('superadmin.inventory');

    // Product and Inventory Management (Restricted for Cashiers)
    // Must be before the products resource so it isn't caught by products/{product}.
    Route::get('products/expiry', [ProductController::class, 'expiryReport'])->name('products.expiry');
    Route::resource('products', ProductController::class);
    Route::resource('stock-transfers', StockTransferController::class);
    Route::post('products/add-stock', [ProductController::class, 'addStock'])->name('stock.add');
    Route::get('stock/receive', [ProductController::class, 'receiveDelivery'])->name('stock.receive');
    Route::post('stock/receive', [ProductController::class, 'processDelivery'])->name('stock.receive.process');
    Route::post('products/{product}/batch-transfer', [ProductController::class, 'batchTransfer'])->name('products.batch-transfer');
    Route::post('categories/quick', [CategoryController::class, 'quickStore'])->name('categories.quick-store');
    Route::resource('categories', CategoryController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::get('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receiveForm'])->name('purchase-orders.receive-form');
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::get('stock-transfers/stock-info', [StockTransferController::class, 'getStockInfo'])->name('stock-transfers.stock-info');
    Route::resource('stock-transfers', StockTransferController::class);

    
    Route::get('pos', [SalesController::class, 'create'])->name('sales.pos');
    Route::get('pos/products', [SalesController::class, 'getProducts'])->name('pos.products');
    Route::get('pos/search', [SalesController::class, 'searchProduct'])->name('pos.search');
    Route::get('sales/create', [SalesController::class, 'create'])->name('sales.create');
    Route::post('sales', [SalesController::class, 'store'])->name('sales.store');
    // Offline POS: service worker / fallback sync endpoint + offline fallback page
    Route::post('sales/sync', [SalesController::class, 'syncOffline'])->name('sales.sync');
    Route::view('offline', 'sales.offline')->name('offline');
    Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
    Route::get('sales/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');

    Route::get('/ai/branch-sales', [AIInventoryController::class, 'branchSalesAnalysis'])->name('ai.branch-sales');
    Route::get('/ai/pricing-health', [AIInventoryController::class, 'kenyanMarketPricing'])->name('ai.pricing-health');

    // Invoice Management
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('invoices/{invoice}/send', [\App\Http\Controllers\InvoiceController::class, 'markAsSent'])->name('invoices.send');
    Route::post('invoices/{invoice}/payment', [\App\Http\Controllers\InvoiceController::class, 'recordPayment'])->name('invoices.payment');
    Route::post('invoices/{invoice}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('sales/{sale}/create-invoice', [\App\Http\Controllers\InvoiceController::class, 'createFromSale'])->name('invoices.from-sale');


    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('shifts/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('shifts/active', [ShiftController::class, 'getActive'])->name('shifts.active');

    Route::resource('expense-categories', ExpenseCategoryController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::patch('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::patch('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');

    // Promotions
    Route::resource('promotions', PromotionController::class);

    // Loans
    Route::resource('loans', \App\Http\Controllers\LoanController::class);
    Route::post('loans/{loan}/payments', [\App\Http\Controllers\LoanController::class, 'recordPayment'])->name('loans.record-payment');

    // AI Inventory Insights & Decision Center
    Route::get('ai/dashboard', [\App\Http\Controllers\AIInventoryController::class, 'dashboard'])->name('ai.dashboard');
    Route::get('ai/product/{product}', [\App\Http\Controllers\AIInventoryController::class, 'productAnalysis'])->name('ai.product');
    Route::get('ai/pricing', [\App\Http\Controllers\AIInventoryController::class, 'pricingDashboard'])->name('ai.pricing');
    Route::get('ai/bundles', [\App\Http\Controllers\AIInventoryController::class, 'bundleSuggestions'])->name('ai.bundles');
    Route::get('ai/waste-management', [\App\Http\Controllers\AIInventoryController::class, 'wasteManagement'])->name('ai.waste');
    
    // AI API Endpoints
    Route::post('api/ai/execute-recommendation', [\App\Http\Controllers\AIInventoryController::class, 'executeRecommendation'])->name('ai.execute');
    Route::get('ai/loss-prevention',       [\App\Http\Controllers\AIInventoryController::class, 'lossPreventionAlerts'])->name('ai.loss-prevention');
    Route::get('ai/smart-purchase-order',  [\App\Http\Controllers\AIInventoryController::class, 'smartPurchaseOrder'])->name('ai.smart-purchase-order');
    Route::get('ai/customer-churn',        [\App\Http\Controllers\AIInventoryController::class, 'customerChurn'])->name('ai.customer-churn');
    Route::get('ai/financial-health',      [\App\Http\Controllers\AIInventoryController::class, 'financialHealth'])->name('ai.financial-health');
    Route::get('ai/promotions',            [\App\Http\Controllers\AIInventoryController::class, 'promotionSuggestions'])->name('ai.promotions');
    Route::get('ai/staff-performance',     [\App\Http\Controllers\AIInventoryController::class, 'staffPerformance'])->name('ai.staff-performance');
    Route::get('ai/seasonal-forecast',     [\App\Http\Controllers\AIInventoryController::class, 'seasonalForecast'])->name('ai.seasonal-forecast');
    Route::get('ai/prediction-accuracy',  [\App\Http\Controllers\AIInventoryController::class, 'predictionAccuracy'])->name('ai.prediction-accuracy');
    Route::get('api/sales/upsell',         [\App\Http\Controllers\SalesController::class, 'upsellSuggestions'])->name('sales.upsell');
    Route::post('api/expenses/suggest-category', [\App\Http\Controllers\ExpenseController::class, 'suggestCategory'])->name('expenses.suggest-category');
    Route::post('api/ai/loan-risk',        [\App\Http\Controllers\AIInventoryController::class, 'loanRiskAssessment'])->name('ai.loan-risk');
    Route::post('api/ai/product-setup',    [\App\Http\Controllers\AIInventoryController::class, 'productSetupHelper'])->name('ai.product-setup');
    Route::get('invoices/{invoice}/payment-reminder', [\App\Http\Controllers\InvoiceController::class, 'paymentReminder'])->name('invoices.payment-reminder');

    Route::get('reports/sales', [\App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/pnl', [\App\Http\Controllers\ReportController::class, 'profitLoss'])->name('reports.pnl');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // System Control (Owner Only)
    Route::get('system/control', [SystemControlController::class, 'index'])->name('system.control');
    Route::post('system/toggle', [SystemControlController::class, 'toggle'])->name('system.toggle');
    Route::post('system/subscription', [SystemControlController::class, 'updateSubscription'])->name('system.subscription.update');

    // Users
    Route::resource('users', UserController::class);

    // Branches (owner/admin only)
    Route::resource('branches', BranchController::class);
    Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])
        ->name('branches.toggle-status');

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// System Unavailable Page (Public if deactivated)
Route::get('system/unavailable', function () {
    return view('errors.system_unavailable');
})->name('system.unavailable');

// Subscription Expired Page
Route::get('system/subscription-expired', function () {
    return view('errors.subscription_expired');
})->name('subscription.expired');
