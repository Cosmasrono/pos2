<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'discount_per_item',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount_per_item' => 'decimal:2',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Methods
    public function calculateLineTotal(): float
    {
        return ($this->quantity * $this->unit_price) - $this->discount_per_item;
    }

    // Automatically calculate line total before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->line_total = ($item->quantity * $item->unit_price) - ($item->discount_per_item ?? 0);
        });
    }
}
