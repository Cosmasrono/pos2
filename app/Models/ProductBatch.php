<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'cost_price',
        'received_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'datetime',
        'quantity'    => 'integer',
        'cost_price'  => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /** Batches with stock left, earliest expiry first (nulls last) — FEFO order. */
    public function scopeFefo($query)
    {
        return $query->where('quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    public function scopeExpired($query, $asOf = null)
    {
        return $query->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $asOf ?? now()->toDateString());
    }

    public function scopeExpiringWithin($query, int $days, $from = null)
    {
        $from = $from ?? now()->toDateString();
        return $query->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $from)
            ->whereDate('expiry_date', '<=', now()->parse($from)->addDays($days)->toDateString());
    }

    /** Stock that may still be sold: no expiry recorded, or not yet expired. */
    public function scopeSellable($query, $asOf = null)
    {
        $asOf = $asOf ?? now()->toDateString();
        return $query->where('quantity', '>', 0)
            ->where(function ($q) use ($asOf) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>=', $asOf);
            });
    }

    /**
     * Number of batches that need attention: already expired OR expiring within
     * `$soonDays`. Optionally scoped to a branch. Used for the sidebar alert badge.
     */
    public static function alertCount(?int $branchId = null, int $soonDays = 30): int
    {
        $query = static::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($soonDays)->toDateString());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->count();
    }

    /** Units of a product at a branch that are NOT expired (sellable). */
    public static function sellableQuantity(int $productId, ?int $branchId): int
    {
        if (! $branchId) {
            return 0;
        }

        return (int) static::query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->sellable()
            ->sum('quantity');
    }

    /**
     * Deduct `qty` units from a product's batches at a branch, earliest-expiry-first.
     * Best-effort: never throws and never drives a batch negative. Returns the
     * number of units actually drawn from tracked batches. product_branch_stocks
     * remains the authoritative quantity; this only keeps the expiry ledger in sync.
     */
    public static function deductFefo(int $productId, ?int $branchId, int $qty): int
    {
        if (! $branchId || $qty <= 0) {
            return 0;
        }

        $remaining = $qty;

        // Only draw from sellable (non-expired) batches, earliest-expiry first.
        $batches = static::query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->sellable()
            ->fefo()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch->quantity, $remaining);
            $batch->quantity -= $take;
            $batch->save();
            $remaining -= $take;
        }

        return $qty - $remaining;
    }

    /**
     * Move `qty` units of a product from one branch to another, earliest-expiry-first,
     * recreating the drawn batches at the destination so expiry data is preserved.
     */
    public static function transferFefo(int $productId, int $fromBranchId, int $toBranchId, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $remaining = $qty;

        $batches = static::query()
            ->where('product_id', $productId)
            ->where('branch_id', $fromBranchId)
            ->fefo()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch->quantity, $remaining);
            $batch->quantity -= $take;
            $batch->save();

            static::create([
                'product_id'   => $productId,
                'branch_id'    => $toBranchId,
                'batch_number' => $batch->batch_number,
                'expiry_date'  => $batch->expiry_date,
                'quantity'     => $take,
                'cost_price'   => $batch->cost_price,
                'received_at'  => now(),
            ]);

            $remaining -= $take;
        }

        // If the branch had untracked stock (batches under-counted), still place the
        // remainder at the destination so totals stay consistent (expiry unknown).
        if ($remaining > 0) {
            static::create([
                'product_id'   => $productId,
                'branch_id'    => $toBranchId,
                'batch_number' => 'TRANSFER',
                'expiry_date'  => null,
                'quantity'     => $remaining,
                'received_at'  => now(),
            ]);
        }
    }
}
