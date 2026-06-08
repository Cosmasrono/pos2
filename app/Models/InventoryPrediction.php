<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPrediction extends Model
{
    protected $fillable = [
        'product_id',
        'prediction_type',
        'prediction_data',
        'confidence_score',
        'predicted_for_date',
    ];

    protected $casts = [
        'prediction_data' => 'array',
        'confidence_score' => 'decimal:2',
        'predicted_for_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
