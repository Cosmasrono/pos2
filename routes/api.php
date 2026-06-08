<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\MpesaController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────
// CART ROUTES
// ─────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{productId}', [CartController::class, 'update']);
        Route::delete('/{productId}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });
});

// ─────────────────────────────────────────────────────────────────
// PRODUCT ROUTES
// ─────────────────────────────────────────────────────────────────

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search/query', [ProductController::class, 'search']);
    Route::get('/barcode/lookup', [ProductController::class, 'byBarcode']);
    Route::get('/inventory/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/inventory/stock-value', [ProductController::class, 'stockValue']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
    });

    // ─────────────────────────────────────────────────────────────────
    // SALES ROUTES
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('sales')->group(function () {
        Route::post('/', [SalesController::class, 'store']);
        Route::get('/{sale}', [SalesController::class, 'show']);
        Route::get('/daily/{date?}', [SalesController::class, 'dailySales']);
        Route::get('/cashier/summary', [SalesController::class, 'cashierSales']);
        Route::post('/{sale}/return', [SalesController::class, 'processReturn']);
        Route::post('/inventory/seed-test-data', [SalesController::class, 'seedTestData']);
    });

    // ─────────────────────────────────────────────────────────────────
    // SHIFT ROUTES
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('shifts')->group(function () {
        Route::post('/open', [ShiftController::class, 'openShift']);
        Route::post('/close', [ShiftController::class, 'closeShift']);
        Route::get('/current', [ShiftController::class, 'currentShift']);
        Route::get('/{shift}/summary', [ShiftController::class, 'summary']);
        Route::get('/history', [ShiftController::class, 'shiftHistory']);
    });

    // ─────────────────────────────────────────────────────────────────
    // FINANCE ROUTES
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('finance')->group(function () {
        Route::post('/expenses', [FinanceController::class, 'recordExpense']);
        Route::post('/expenses/{expense}/approve', [FinanceController::class, 'approveExpense']);
        Route::post('/expenses/{expense}/reject', [FinanceController::class, 'rejectExpense']);
        Route::post('/income', [FinanceController::class, 'recordOtherIncome']);
        Route::get('/daily-summary', [FinanceController::class, 'dailyFinancialSummary']);
        Route::get('/profit-and-loss', [FinanceController::class, 'profitAndLoss']);
        Route::get('/expense-breakdown', [FinanceController::class, 'expenseBreakdown']);
        Route::get('/monthly-trend', [FinanceController::class, 'monthlyTrend']);
        Route::get('/pending-expenses', [FinanceController::class, 'pendingExpenses']);
    });

    // ─────────────────────────────────────────────────────────────────
    // PURCHASE ROUTES
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('purchases')->group(function () {
        Route::get('/suppliers', [PurchaseController::class, 'suppliers']);
        Route::post('/suppliers', [PurchaseController::class, 'createSupplier']);
        Route::post('/orders', [PurchaseController::class, 'createPurchaseOrder']);
        Route::post('/orders/{po}/receive', [PurchaseController::class, 'receivePurchaseOrder']);
        Route::post('/orders/{po}/payment', [PurchaseController::class, 'recordPayment']);
        Route::get('/orders', [PurchaseController::class, 'purchaseOrders']);
        Route::get('/orders/pending', [PurchaseController::class, 'pendingOrders']);
        Route::get('/orders/{po}', [PurchaseController::class, 'purchaseOrderDetail']);
        Route::get('/suppliers/{supplier}/balance', [PurchaseController::class, 'supplierBalance']);
    });

    // ─────────────────────────────────────────────────────────────────
    // M-PESA ROUTES
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('mpesa')->group(function () {
        Route::post('/initiate', [MpesaController::class, 'initiate']);
        Route::get('/status/{checkoutRequestId}', [MpesaController::class, 'status']);
    });

    // ─────────────────────────────────────────────────────────────────
    // DELIVERY ROUTES - AUTHENTICATED
    // ─────────────────────────────────────────────────────────────────

    Route::prefix('delivery')->group(function () {
        // Product and inventory endpoints
        Route::get('/products', [DeliveryController::class, 'getDeliveryProducts']);
        Route::get('/inventory', [DeliveryController::class, 'getInventory']);

        // Order management
        Route::post('/orders', [DeliveryController::class, 'processOrder']);
        Route::get('/orders', [DeliveryController::class, 'getOrders']);
        Route::get('/orders/{id}', [DeliveryController::class, 'getOrderDetails']);
        Route::get('/orders/{id}/customer', [DeliveryController::class, 'getCustomerInfo']);
        Route::post('/orders/{orderNumber}/payment', [DeliveryController::class, 'updatePaymentStatus']);

        // Order status updates
        Route::post('/orders/{id}/status', [DeliveryController::class, 'updateDeliveryStatus']);
        Route::post('/orders/{id}/picked-up', [DeliveryController::class, 'markPickedUp']);
        Route::post('/orders/{id}/delivered', [DeliveryController::class, 'markDelivered']);
        Route::post('/orders/{id}/failed', [DeliveryController::class, 'markDeliveryFailed']);

        // Statistics
        Route::get('/stats', [DeliveryController::class, 'getDeliveryStats']);
    });
});

// ─────────────────────────────────────────────────────────────────
// PUBLIC ROUTES (No Authentication Required)
// ─────────────────────────────────────────────────────────────────

// Delivery System Login
Route::post('/delivery/login', [DeliveryController::class, 'login']);

// M-Pesa Callback (from Safaricom)
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('api.mpesa.callback');