<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'customer_type',
        'can_buy_on_credit',
        'credit_limit',
        'current_credit_balance',
    ];

    protected $casts = [
        'can_buy_on_credit' => 'boolean',
        'credit_limit' => 'decimal:2',
        'current_credit_balance' => 'decimal:2',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function remainingCredit(): float
    {
        return $this->credit_limit - $this->current_credit_balance;
    }

    public function canBuyOnCredit(): bool
    {
        return $this->can_buy_on_credit && $this->remainingCredit() > 0;
    }

    public function getTotalPurchases(): float
    {
        return $this->sales()->where('status', 'completed')->sum('total_amount');
    }

    public function getTotalTransactions(): int
    {
        return $this->sales()->where('status', 'completed')->count();
    }

    public function getTotalInvoicesAmount(): float
    {
        return $this->invoices()->sum('total_amount');
    }

    public function getOutstandingInvoicesBalance(): float
    {
        return $this->invoices()->sum('balance_due');
    }

}
