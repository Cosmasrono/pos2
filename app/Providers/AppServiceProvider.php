<?php

namespace App\Providers;

use App\Models\ProductBatch;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share the expiry-alert count (expired + expiring within 30 days) with the
        // sidebar. Scoped to the user's branch; guarded so migrations/console don't break.
        View::composer('layouts.app', function ($view) {
            $count = 0;
            try {
                if (auth()->check() && Schema::hasTable('product_batches')) {
                    $count = ProductBatch::alertCount(auth()->user()->branch_id, 30);
                }
            } catch (\Throwable $e) {
                $count = 0;
            }
            $view->with('expiryAlertCount', $count);
        });
    }
}
