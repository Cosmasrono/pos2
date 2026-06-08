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
        Schema::create('prediction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('prediction_type');
            $table->decimal('predicted_value', 15, 2);
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('accuracy_score', 5, 2)->nullable();
            $table->date('prediction_date');
            $table->date('actual_date')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'prediction_type']);
            $table->index('prediction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_logs');
    }
};
