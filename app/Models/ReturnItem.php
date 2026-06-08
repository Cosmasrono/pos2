<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'product_id',
        'quantity',
        'refund_per_item',
    ];

    protected $casts = [
        'refund_per_item' => 'decimal:2',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(Return_::class, 'return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
