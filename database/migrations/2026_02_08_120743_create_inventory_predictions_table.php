<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('prediction_type'); // e.g., demand_forecast, reorder_recommendation, trend_analysis
            $table->json('prediction_data');
            $table->decimal('confidence_score', 5, 2);
            $table->date('predicted_for_date');
            $table->timestamps();

            $table->index(['product_id', 'prediction_type']);
            $table->index('predicted_for_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_predictions');
    }
};
