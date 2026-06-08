<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the problematic unique constraint that treats NULL as duplicate
        Schema::table('products', function (Blueprint $table) {
            try {
                // Drop existing unique constraint if it exists
                $table->dropUnique('products_barcode_unique');
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        });

        // Add filtered unique index for SQL Server that allows multiple NULLs
        DB::statement('
            CREATE UNIQUE INDEX products_barcode_unique 
            ON products(barcode) 
            WHERE barcode IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_barcode_unique ON products');
    }
};
