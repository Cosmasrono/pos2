<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expenses
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'mpesa'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('no action');
            
            $table->index('category_id');
            $table->index('expense_date');
            $table->index('status');
        });

        // Other income
        Schema::create('other_income', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->date('income_date');
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
            
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('no action');
            
            $table->index('income_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_income');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
