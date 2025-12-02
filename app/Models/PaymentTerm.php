<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PaymentTerm extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';

    /**
     * Relationship with Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Get payment progress percentage
     */
    public function getPaymentProgressAttribute(): float
    {
        if ($this->amount <= 0) return 0;
        return ($this->paid_amount / $this->amount) * 100;
    }

    /**
     * Check if overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && !$this->isPaid();
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    /**
     * Days until due
     */
    public function getDaysUntilDueAttribute(): int
    {
        return Carbon::now()->diffInDays($this->due_date, false);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for overdue payments
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_PAID]);
    }

    /**
     * Scope for due soon (within days)
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>=', now())
            ->whereNotIn('status', [self::STATUS_PAID]);
    }

    /**
     * Add payment
     */
    public function addPayment(float $amount, string $notes = null): void
    {
        $this->increment('paid_amount', $amount);
        
        // Update status
        if ($this->paid_amount >= $this->amount) {
            $this->update([
                'status' => self::STATUS_PAID,
                'payment_date' => now(),
            ]);
        } elseif ($this->paid_amount > 0) {
            $this->update(['status' => self::STATUS_PARTIAL]);
        }

        if ($notes) {
            $this->update(['notes' => $this->notes . "\n" . now()->format('Y-m-d') . ": " . $notes]);
        }
    }

    /**
     * Update overdue status
     */
    public static function updateOverdueStatus(): int
    {
        return self::where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_PAID])
            ->update(['status' => self::STATUS_OVERDUE]);
    }
}
