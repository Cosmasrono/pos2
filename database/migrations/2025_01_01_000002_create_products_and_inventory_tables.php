<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Products table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique(); // Stock Keeping Unit
            $table->string('barcode')->nullable();
            $table->decimal('cost_price', 12, 2); // Cost per single item
            $table->decimal('selling_price', 12, 2); // Selling price per single item
            $table->integer('quantity_in_stock')->default(0);
            $table->decimal('total_cost', 12, 2)->default(0); // Auto-calculated: cost_price × quantity_in_stock
            $table->integer('reorder_level')->default(10);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Filtered unique index for nullable barcode (allows multiple NULLs)
            $table->unique('barcode', null, null, null, 'where barcode is not null');
            $table->index('barcode');
            $table->index('category_id');
        });

        // Stock movements table (audit trail for inventory)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'damage', 'return']);
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
        });

        // Stock take (physical inventory count)
        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', ['in-progress', 'completed', 'cancelled'])->default('in-progress');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('no action');
        });

        // Stock take items (individual products in a stock take)
        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_quantity');
            $table->integer('counted_quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['stock_take_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
