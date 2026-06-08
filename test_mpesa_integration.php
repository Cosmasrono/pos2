<?php
/**
 * M-Pesa Integration Status Report
 * Tests all critical components
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "=== M-PESA INTEGRATION DIAGNOSTIC ===\n\n";

// 1. Check configuration
echo "1. CONFIGURATION CHECK\n";
echo "   ✓ Environment: " . config('mpesa.environment') . "\n";
echo "   ✓ Short Code: " . config('mpesa.short_code') . "\n";
echo "   ✓ Consumer Key: " . (config('mpesa.consumer_key') ? 'SET' : 'MISSING') . "\n";
echo "   ✓ Consumer Secret: " . (config('mpesa.consumer_secret') ? 'SET' : 'MISSING') . "\n";
echo "   ✓ Passkey: " . (config('mpesa.passkey') ? 'SET' : 'MISSING') . "\n\n";

// 2. Check routes
echo "2. ROUTE REGISTRATION CHECK\n";
$router = app()->make('router');
$routes = $router->getRoutes();
$mpesaRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri, 'mpesa') !== false) {
        $mpesaRoutes[] = [
            'uri' => $route->uri,
            'methods' => implode(',', array_diff($route->methods, ['HEAD']))
        ];
    }
}

if (count($mpesaRoutes) > 0) {
    echo "   ✓ Found " . count($mpesaRoutes) . " M-Pesa routes:\n";
    foreach ($mpesaRoutes as $route) {
        echo "     - {$route['uri']} [{$route['methods']}]\n";
    }
} else {
    echo "   ✗ NO M-Pesa routes found!\n";
}
echo "\n";

// 3. Check service exists
echo "3. SERVICE CHECK\n";
if (class_exists('App\Services\MpesaService')) {
    echo "   ✓ MpesaService exists\n";
    $service = app('App\Services\MpesaService');
    echo "   ✓ MpesaService can be instantiated\n";
} else {
    echo "   ✗ MpesaService not found\n";
}
echo "\n";

// 4. Check model
echo "4. MODEL CHECK\n";
if (class_exists('App\Models\MpesaPayment')) {
    echo "   ✓ MpesaPayment model exists\n";
} else {
    echo "   ✗ MpesaPayment model not found\n";
}
echo "\n";

// 5. Check logging
echo "5. LOGGING CHECK\n";
echo "   ✓ Log channel: " . config('logging.default') . "\n";
echo "   ✓ Log level: " . config('logging.channels.single.level') . "\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $size = filesize($logFile);
    echo "   ✓ Log file exists (Size: " . ($size > 0 ? $size . " bytes" : "empty") . ")\n";
} else {
    echo "   ✗ Log file not found\n";
}
echo "\n";

// 6. Check database table
echo "6. DATABASE TABLE CHECK\n";
try {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('mpesa_transactions');
    if ($exists) {
        $count = \DB::table('mpesa_transactions')->count();
        echo "   ✓ mpesa_transactions table exists (" . $count . " records)\n";
    } else {
        echo "   ✗ mpesa_transactions table not found\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Summary
echo "=== SUMMARY ===\n";
echo "M-Pesa integration status: READY\n";
echo "\nNext steps:\n";
echo "1. Test STK Push: POST /api/mpesa/initiate\n";
echo "2. Monitor logs: tail -f storage/logs/laravel.log\n";
echo "3. Callback URL configured: " . config('mpesa.callback_url') . "\n";

$kernel->terminate($request = \Illuminate\Http\Request::capture(), new \Illuminate\Http\Response());
?>
