<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use \App\Traits\Auditable;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'quantity_in_stock',   // ← added back - this is now the main stock field
        'reorder_level',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'cost_price'        => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'quantity_in_stock' => 'integer',
        'reorder_level'     => 'integer',
        'category_id'       => 'integer',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    protected $appends = [
        'total_cost_value',
        'total_selling_value',
        'profit',
        'profit_margin',
    ];

    // ────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function branchStocks(): HasMany
    {
        return $this->hasMany(ProductBranchStock::class);
    }

    // If you added branch_id to products (optional)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // ────────────────────────────────────────────────
    // Accessors
    // ────────────────────────────────────────────────

    public function getTotalCostValueAttribute(): float
    {
        return round($this->cost_price * $this->quantity_in_stock, 2);
    }

    public function getTotalSellingValueAttribute(): float
    {
        return round($this->selling_price * $this->quantity_in_stock, 2);
    }

    public function getProfitAttribute(): float
    {
        return $this->selling_price - $this->cost_price;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->selling_price == 0) {
            return 0.0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->selling_price) * 100, 2);
    }

    // ────────────────────────────────────────────────
    // Helper Methods
    // ────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        // Simple global check
        $stock = $this->quantity_in_stock ?? 0;
        $threshold = $this->reorder_level ?? 0;

        return $stock > 0 && $stock <= $threshold;
    }

    // ────────────────────────────────────────────────
    // Scopes
    // ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_in_stock <= COALESCE(reorder_level, 0)')
                     ->where('is_active', true)
                     ->where('quantity_in_stock', '>', 0);
    }
}