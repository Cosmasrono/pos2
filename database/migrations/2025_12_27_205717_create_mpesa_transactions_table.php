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
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            
            // M-Pesa Request Details
            $table->string('merchant_request_id')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->index();
            
            // Payment Details
            $table->string('phone_number', 15);
            $table->decimal('amount', 12, 2);
            $table->string('account_reference')->nullable(); // Sale reference
            $table->string('transaction_desc')->nullable();
            
            // Status Tracking
            $table->enum('status', ['initiated', 'pending', 'confirmed', 'failed', 'cancelled'])->default('pending');
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            
            // M-Pesa Response Details
            $table->string('mpesa_receipt_number')->nullable()->index();
            $table->string('transaction_code')->nullable()->index();
            $table->timestamp('transaction_date')->nullable();
            
            // Relations
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Cashier
            
            // Callback Data (store full response for debugging)
            $table->json('response_data')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index(['phone_number', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
