<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'loan_number',
        'product_description',
        'total_amount',
        'amount_paid',
        'interest_rate',
        'loan_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'loan_date' => 'date',
        'due_date' => 'date',
    ];

    protected $appends = ['balance', 'is_overdue', 'days_overdue'];

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    /**
     * Accessors
     */
    public function getBalanceAttribute(): float
    {
        return (float) ($this->total_amount - $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active' && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(now());
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '<', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Business Logic Methods
     */
    public function recordPayment(float $amount, string $method, ?string $reference = null, ?string $notes = null): LoanPayment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'payment_method' => $method,
            'payment_date' => now(),
            'reference_number' => $reference,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->amount_paid += $amount;
        $this->save();

        $this->updateStatus();

        return $payment;
    }

    public function updateStatus(): void
    {
        if ($this->balance <= 0) {
            $this->status = 'paid';
        } elseif ($this->due_date->isPast() && $this->status === 'active') {
            $this->status = 'overdue';
        }

        $this->save();
    }

    public function calculateInterest(): float
    {
        if (!$this->interest_rate) {
            return 0;
        }

        $principal = $this->balance;
        $rate = $this->interest_rate / 100;
        $daysOverdue = max(0, $this->days_overdue);

        // Simple interest calculation: Principal * Rate * (Days / 365)
        return $principal * $rate * ($daysOverdue / 365);
    }

    /**
     * Generate unique loan number
     */
    public static function generateLoanNumber(): string
    {
        $year = now()->year;
        $lastLoan = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastLoan ? ((int) substr($lastLoan->loan_number, -4)) + 1 : 1;

        return sprintf('LOAN-%d-%04d', $year, $number);
    }
}
