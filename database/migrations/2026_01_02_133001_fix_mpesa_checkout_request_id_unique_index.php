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
        /*
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            // Drop the unique constraint
            // $table->dropUnique('mpesa_transactions_checkout_request_id_unique');
            
            // Add a regular index for performance
            $table->index('checkout_request_id');
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            // Drop the regular index
            $table->dropIndex(['checkout_request_id']);
            
            // Restore the unique constraint
            $table->unique('checkout_request_id', 'mpesa_transactions_checkout_request_id_unique');
        });
        */
    }
};
