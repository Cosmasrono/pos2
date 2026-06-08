<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaPayment extends Model
{
    protected $table = 'mpesa_transactions';
    
    protected $fillable = [
        'sale_id',
        'user_id',
        'merchant_request_id',
        'checkout_request_id',
        'phone_number',
        'amount',
        'account_reference',
        'transaction_desc',
        'transaction_code',
        'mpesa_receipt_number',
        'transaction_date',
        'status',
        'result_code',
        'result_desc',
        'response_data',
        'confirmed_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'transaction_date' => 'datetime',
        'response_data' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
