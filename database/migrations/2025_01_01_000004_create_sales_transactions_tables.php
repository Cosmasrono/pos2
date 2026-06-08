<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shifts (cashier shifts) - created here for foreign key dependency
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_id');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->enum('status', ['open', 'closed', 'discrepancy'])->default('open');
            
            // Opening
            $table->decimal('opening_cash', 12, 2);
            $table->text('opening_notes')->nullable();
            
            // Closing
            $table->decimal('closing_cash_counted', 12, 2)->nullable();
            $table->text('closing_notes')->nullable();
            
            // Calculations
            $table->decimal('total_cash_sales', 12, 2)->default(0);
            $table->decimal('total_mpesa_sales', 12, 2)->default(0);
            $table->decimal('total_card_sales', 12, 2)->default(0);
            $table->decimal('total_refunds', 12, 2)->default(0);
            $table->decimal('expected_closing_cash', 12, 2)->nullable();
            $table->decimal('cash_shortage_overage', 12, 2)->nullable();
            
            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
            
            $table->foreign('opened_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('no action');
            
            $table->index('cashier_id');
            $table->index('status');
            $table->index('opened_at');
        });

        // Customers - created here for foreign key dependency
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->unique();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('customer_type', ['walk_in', 'registered'])->default('walk_in');
            $table->boolean('can_buy_on_credit')->default(false);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('current_credit_balance', 12, 2)->default(0);
            $table->timestamps();
            
            $table->index('phone');
            $table->index('customer_type');
        });

        // Sales transactions (receipts)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('cashier_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('status', ['completed', 'cancelled', 'refunded'])->default('completed');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('primary_payment_method', ['cash', 'mpesa', 'card'])->default('cash');
            $table->decimal('cash_paid', 12, 2)->default(0);
            $table->decimal('mpesa_paid', 12, 2)->default(0);
            $table->decimal('card_paid', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->timestamps();
            
            $table->index('cashier_id');
            $table->index('customer_id');
            $table->index('created_at');
            $table->index('status');
            
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('no action');
        });

        // Sale items (individual products in a sale)
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->decimal('discount_per_item', 12, 2)->default(0);
            $table->timestamps();
            
            $table->index('sale_id');
            $table->index('product_id');
        });

        // M-PESA payment tracking
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number');
            $table->decimal('amount', 12, 2);
            $table->string('transaction_code')->nullable()->unique();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->text('response_data')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('phone_number');
            $table->index('status');
            $table->index(['created_at', 'status']);
        });

        // Returns and refunds
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('return_reference')->unique();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->decimal('refund_amount', 12, 2);
            $table->enum('refund_method', ['cash', 'mpesa', 'credit'])->default('cash');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('no action');
            
            $table->index('sale_id');
            $table->index('status');
        });

        // Return items
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('refund_per_item', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('mpesa_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('shifts');
    }
};
