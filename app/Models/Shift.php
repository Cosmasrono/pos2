<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use \App\Traits\BranchScoped;

    protected $fillable = [
        'cashier_id',
        'branch_id',
        'opened_at',
        'closed_at',
        'status',
        'opening_cash',
        'opening_notes',
        'closing_cash_counted',
        'closing_notes',
        'total_cash_sales',
        'total_mpesa_sales',
        'total_card_sales',
        'total_refunds',
        'expected_closing_cash',
        'cash_shortage_overage',
        'opened_by',
        'closed_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash_counted' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_mpesa_sales' => 'decimal:2',
        'total_card_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'expected_closing_cash' => 'decimal:2',
        'cash_shortage_overage' => 'decimal:2',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function calculateExpectedClosing(): float
    {
        return $this->opening_cash + $this->total_cash_sales - $this->total_refunds;
    }

    public function branch()
{
    return $this->belongsTo(Branch::class);
}
}
