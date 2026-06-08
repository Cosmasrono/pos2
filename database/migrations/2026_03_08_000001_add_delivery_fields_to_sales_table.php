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
        Schema::table('sales', function (Blueprint $table) {
            // Add delivery tracking fields
            $table->string('delivery_status')->default('pending')->after('status'); // pending, picked_up, in_transit, delivered, failed
            $table->text('delivery_notes')->nullable()->after('delivery_status');
            $table->date('delivery_date')->nullable()->after('delivery_notes');
            $table->timestamp('picked_up_at')->nullable()->after('delivery_date');
            $table->timestamp('delivered_at')->nullable()->after('picked_up_at');
            $table->longText('delivery_proof')->nullable()->after('delivered_at'); // for base64 image or file path

            // Add indexes for better query performance
            $table->index('delivery_status');
            $table->index('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['delivery_status']);
            $table->dropIndex(['delivery_date']);
            $table->dropColumn([
                'delivery_status',
                'delivery_notes',
                'delivery_date',
                'picked_up_at',
                'delivered_at',
                'delivery_proof',
            ]);
        });
    }
};
