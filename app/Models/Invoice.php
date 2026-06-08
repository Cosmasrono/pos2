<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Invoice extends Model
{
    use \App\Traits\Auditable;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sale_id',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'payment_terms',
        'notes',
        'created_by',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date < now() && $this->balance_due > 0 && !in_array($this->status, ['paid', 'cancelled']);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'paid' => 'success',
            'partial' => 'warning',
            'overdue' => 'danger',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    // Methods
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";
        
        // Get the last invoice number for this year
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function recordPayment(float $amount, string $method, ?string $reference = null, ?string $notes = null): InvoicePayment
    {
        // Create payment record
        $payment = $this->payments()->create([
            'payment_date' => now(),
            'amount' => $amount,
            'payment_method' => $method,
            'reference_number' => $reference,
            'notes' => $notes,
            'recorded_by' => auth()->id(),
        ]);

        // Update invoice amounts
        $newAmountPaid = $this->amount_paid + $amount;
        $newBalanceDue = $this->total_amount - $newAmountPaid;

        // Determine new status
        $newStatus = $this->status;
        if ($newBalanceDue <= 0) {
            $newStatus = 'paid';
            $paidAt = now();
        } elseif ($newAmountPaid > 0 && $newBalanceDue > 0) {
            $newStatus = 'partial';
            $paidAt = null;
        }

        $this->update([
            'amount_paid' => $newAmountPaid,
            'balance_due' => $newBalanceDue,
            'status' => $newStatus,
            'paid_at' => $paidAt ?? $this->paid_at,
        ]);

        return $payment;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');
        $total = $subtotal + $this->tax_amount - $this->discount_amount;
        $balanceDue = $total - $this->amount_paid;

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
            'balance_due' => $balanceDue,
        ]);
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    public function canDelete(): bool
    {
        return in_array($this->status, ['draft', 'cancelled']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    // Update overdue status automatically
    public function checkAndUpdateOverdueStatus(): void
    {
        if ($this->is_overdue && $this->status !== 'overdue') {
            $this->update(['status' => 'overdue']);
        }
    }
}
