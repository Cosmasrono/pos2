<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                // Add stock distribution percentage column if it doesn't exist
                if (!Schema::hasColumn('branches', 'stock_distribution_percentage')) {
                    $table->decimal('stock_distribution_percentage', 5, 2)->default(0)->comment('Percentage of new stock to allocate to this branch (e.g., 20.00 for 20%)');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (Schema::hasColumn('branches', 'stock_distribution_percentage')) {
                    $table->dropColumn('stock_distribution_percentage');
                }
            });
        }
    }
};
