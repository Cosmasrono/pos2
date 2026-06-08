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
        Schema::table('product_branch_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_branch_stocks', 'initial_allocation')) {
                $table->integer('initial_allocation')->default(0)->after('quantity_in_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_branch_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_branch_stocks', 'initial_allocation')) {
                $table->dropColumn('initial_allocation');
            }
        });
    }
};
