<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCredit extends Model
{
    protected $fillable = [
        'customer_id',
        'credit_limit',
        'current_debt',
        'available_credit',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Calculate and update available credit
     */
    public function updateAvailableCredit(): void
    {
        $this->update([
            'available_credit' => $this->credit_limit - $this->current_debt
        ]);
    }

    /**
     * Check if customer has sufficient credit
     */
    public function hasSufficientCredit(float $amount): bool
    {
        return $this->is_active && ($this->available_credit >= $amount);
    }

    /**
     * Use credit (increase debt)
     */
    public function useCredit(float $amount): bool
    {
        if (!$this->hasSufficientCredit($amount)) {
            return false;
        }

        $this->increment('current_debt', $amount);
        $this->updateAvailableCredit();
        
        return true;
    }

    /**
     * Release credit (decrease debt)
     */
    public function releaseCredit(float $amount): void
    {
        $this->decrement('current_debt', min($amount, $this->current_debt));
        $this->updateAvailableCredit();
    }

    /**
     * Get credit utilization percentage
     */
    public function getCreditUtilizationAttribute(): float
    {
        if ($this->credit_limit <= 0) return 0;
        
        return ($this->current_debt / $this->credit_limit) * 100;
    }

    /**
     * Check if credit is near limit
     */
    public function isNearLimit(float $threshold = 80): bool
    {
        return $this->credit_utilization >= $threshold;
    }

    /**
     * Get formatted credit limit
     */
    public function getFormattedCreditLimitAttribute(): string
    {
        return 'Rp ' . number_format($this->credit_limit, 0, ',', '.');
    }

    /**
     * Get formatted current debt
     */
    public function getFormattedCurrentDebtAttribute(): string
    {
        return 'Rp ' . number_format($this->current_debt, 0, ',', '.');
    }

    /**
     * Get formatted available credit
     */
    public function getFormattedAvailableCreditAttribute(): string
    {
        return 'Rp ' . number_format($this->available_credit, 0, ',', '.');
    }

    /**
     * Scope for active credits
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for customers with debt
     */
    public function scopeWithDebt($query)
    {
        return $query->where('current_debt', '>', 0);
    }

    /**
     * Scope for customers near credit limit
     */
    public function scopeNearLimit($query, float $threshold = 80)
    {
        return $query->whereRaw('(current_debt / credit_limit * 100) >= ?', [$threshold]);
    }

    /**
     * Create or update customer credit
     */
    public static function setCustomerCredit(int $customerId, float $creditLimit, bool $isActive = true): self
    {
        return self::updateOrCreate(
            ['customer_id' => $customerId],
            [
                'credit_limit' => $creditLimit,
                'is_active' => $isActive,
                'available_credit' => $creditLimit - (self::where('customer_id', $customerId)->value('current_debt') ?? 0)
            ]
        );
    }
}
