<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PredictionLog;
use App\Services\AIInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailyPredictions extends Command
{
    protected $signature   = 'predictions:generate';
    protected $description = 'Generate 30-day demand predictions for all active products and reconcile past predictions against actuals';

    public function handle(AIInventoryService $service): int
    {
        $this->info('=== Daily Prediction Run: ' . now()->toDateTimeString() . ' ===');

        // --- Phase 1: Generate fresh predictions for all active products ---
        $products = Product::where('is_active', true)
            ->where('quantity_in_stock', '>=', 0)
            ->get();

        $this->info("Generating predictions for {$products->count()} products...");
        $generated = 0;

        foreach ($products as $product) {
            try {
                $service->predictDemand($product, 30);
                $generated++;
            } catch (\Exception $e) {
                Log::error("Prediction failed for product {$product->id} ({$product->name}): " . $e->getMessage());
            }
        }

        $this->info("Generated {$generated} predictions.");

        // --- Phase 2: Reconcile 30-day-old predictions with actual sales ---
        $this->info('Reconciling past predictions against actuals...');
        $reconciled = 0;
        $totalAccuracy = 0;

        // Find prediction_logs made ~30 days ago that have no actual_value yet
        $pastPredictions = PredictionLog::whereNull('actual_value')
            ->where('prediction_date', '<=', now()->subDays(30)->toDateString())
            ->where('prediction_type', 'demand_forecast')
            ->with('product')
            ->get();

        foreach ($pastPredictions as $log) {
            try {
                $actualQty = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sale_items.product_id', $log->product_id)
                    ->where('sales.status', 'completed')
                    ->whereBetween('sales.created_at', [
                        $log->prediction_date,
                        now()->subDays(1)->toDateString(),
                    ])
                    ->sum('sale_items.quantity');

                // Accuracy: how close was the prediction? 100% = exact match
                // Use MAPE-style: 1 - (|predicted - actual| / max(actual, 1))
                $accuracy = $log->predicted_value > 0 || $actualQty > 0
                    ? max(0, 1 - (abs($log->predicted_value - $actualQty) / max($actualQty, 1))) * 100
                    : 100; // both zero = perfect

                $log->actual_value  = $actualQty;
                $log->actual_date   = now()->toDateString();
                $log->accuracy_score = round($accuracy, 2);
                $log->save();

                $totalAccuracy += $accuracy;
                $reconciled++;
            } catch (\Exception $e) {
                Log::error("Reconciliation failed for prediction log {$log->id}: " . $e->getMessage());
            }
        }

        if ($reconciled > 0) {
            $avgAccuracy = round($totalAccuracy / $reconciled, 1);
            $this->info("Reconciled {$reconciled} predictions. Average accuracy: {$avgAccuracy}%");

            if ($avgAccuracy < 80) {
                Log::warning("Prediction accuracy is below 80% target: {$avgAccuracy}%");
                $this->warn("WARNING: Accuracy {$avgAccuracy}% is below the 80% target. Review thin-data products.");
            }
        } else {
            $this->info('No past predictions to reconcile yet.');
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
