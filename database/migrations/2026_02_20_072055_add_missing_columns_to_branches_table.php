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
    Schema::table('branches', function (Blueprint $table) {
        if (!Schema::hasColumn('branches', 'code')) {
            $table->string('code')->nullable()->unique();
        }
        if (!Schema::hasColumn('branches', 'address')) {
            $table->string('address')->nullable();
        }
        if (!Schema::hasColumn('branches', 'phone')) {
            $table->string('phone')->nullable();
        }
        if (!Schema::hasColumn('branches', 'is_main')) {
            $table->boolean('is_main')->default(false);
        }
        if (!Schema::hasColumn('branches', 'is_active')) {
            $table->boolean('is_active')->default(true);
        }
        if (!Schema::hasColumn('branches', 'stock_distribution_percentage')) {
            $table->decimal('stock_distribution_percentage', 5, 2)->nullable()->default(0);
        }
    });
}

public function down(): void
{
    Schema::table('branches', function (Blueprint $table) {
        $table->dropColumn(['code', 'address', 'phone', 'is_main', 'is_active', 'stock_distribution_percentage']);
    });
}
};
