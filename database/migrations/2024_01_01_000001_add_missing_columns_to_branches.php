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
                // Add missing columns if they don't exist
                if (!Schema::hasColumn('branches', 'code')) {
                    $table->string('code')->nullable()->unique();
                }
                if (!Schema::hasColumn('branches', 'address')) {
                    $table->text('address')->nullable();
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
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $columns = ['code', 'address', 'phone', 'is_main', 'is_active'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('branches', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
