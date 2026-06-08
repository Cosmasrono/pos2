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
        // Drop the existing check constraint on 'type' if it exists.
        // SQL Server enum implementation in Laravel uses CHECK constraints.
        $constraint = DB::selectOne("
            SELECT name 
            FROM sys.check_constraints 
            WHERE parent_object_id = OBJECT_ID('stock_movements') 
            AND definition LIKE '%type%'
        ");

        if ($constraint) {
            DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT [{$constraint->name}]");
        }

        // Update the column to allow the new types
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });

        // Add the updated check constraint
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT [CK_stock_movements_type] 
            CHECK ([type] IN ('purchase', 'sale', 'adjustment', 'damage', 'return', 'transfer', 'restock'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original allowed types
        DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT [CK_stock_movements_type]");

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });

        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT [CK_stock_movements_type_orig] 
            CHECK ([type] IN ('purchase', 'sale', 'adjustment', 'damage', 'return'))");
    }


};
