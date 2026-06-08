<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use \App\Traits\Auditable, \App\Traits\BranchScoped;

    protected $fillable = [
        'receipt_number',
        'cashier_id',
        'customer_id',
        'branch_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'trade_in_amount',
        'total_amount',
        'primary_payment_method',
        'cash_paid',
        'mpesa_paid',
        'card_paid',
        'change_amount',
        'notes',
        'shift_id',
        'delivery_status',
        'delivery_notes',
        'delivery_date',
        'picked_up_at',
        'delivered_at',
        'delivery_proof',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trade_in_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cash_paid' => 'decimal:2',
        'mpesa_paid' => 'decimal:2',
        'card_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'cashier_id' => 'integer',
        'delivery_date' => 'date',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function mpesaPayments(): HasMany
    {
        return $this->hasMany(MpesaPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(Return_::class);
    }

    public function tradeIns(): HasMany
    {
        return $this->hasMany(TradeIn::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function getTotalQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Scope a query to only include sales for the current user if they are a cashier.
     */
    public function scopeForCurrentUser($query)
    {
        $user = auth()->user();
        if ($user && $user->isOwner()) {
            return $query;
        }
        if ($user && $user->isCashier() && !$user->isSuperAdmin() && !$user->isManager()) {
            return $query->where('cashier_id', $user->id);
        }
        return $query;
    }
}
