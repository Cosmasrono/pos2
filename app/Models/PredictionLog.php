<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionLog extends Model
{
    protected $fillable = [
        'product_id',
        'prediction_type',
        'predicted_value',
        'actual_value',
        'accuracy_score',
        'prediction_date',
        'actual_date',
    ];

    protected $casts = [
        'predicted_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'prediction_date' => 'date',
        'actual_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
