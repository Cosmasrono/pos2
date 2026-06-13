<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_batches')) {
            return;
        }

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity')->default(0);          // units remaining in this batch
            $table->decimal('cost_price', 12, 2)->nullable(); // per-unit cost for this batch
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'branch_id']);
            $table->index('expiry_date');
        });

        // Backfill: turn existing per-branch stock into a single "legacy" batch each,
        // so batches sum to current stock and FEFO works immediately. Expiry unknown.
        if (Schema::hasTable('product_branch_stocks')) {
            $now = now();
            DB::table('product_branch_stocks')
                ->where('quantity_in_stock', '>', 0)
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($now) {
                    $insert = [];
                    foreach ($rows as $r) {
                        $insert[] = [
                            'product_id'   => $r->product_id,
                            'branch_id'    => $r->branch_id,
                            'batch_number' => 'LEGACY',
                            'expiry_date'  => null,
                            'quantity'     => $r->quantity_in_stock,
                            'cost_price'   => null,
                            'received_at'  => $now,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                    if ($insert) {
                        DB::table('product_batches')->insert($insert);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
