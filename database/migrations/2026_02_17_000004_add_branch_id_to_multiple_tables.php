<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add branch_id to sales table
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'branch_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('no action');
                $table->index('branch_id');
            });
        }

        // Add branch_id to shifts table
        if (Schema::hasTable('shifts') && !Schema::hasColumn('shifts', 'branch_id')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('no action');
                $table->index('branch_id');
            });
        }

        // Add branch_id to stock_movements table
        if (Schema::hasTable('stock_movements') && !Schema::hasColumn('stock_movements', 'branch_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('no action');
                $table->index('branch_id');
            });
        }

        // Add branch_id to purchase_orders table
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'branch_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('no action');
                $table->index('branch_id');
            });
        }

        // Add branch_id to expenses table
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'branch_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('no action');
                $table->index('branch_id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['sales', 'shifts', 'stock_movements', 'purchase_orders', 'expenses'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign(["{$tableName}_branch_id_foreign"]);
                    $table->dropIndex(["{$tableName}_branch_id_index"]);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};