<?php

namespace App\Traits;

use App\Models\CustomerCredit;
use App\Models\PaymentTerm;
use App\Services\CreditValidator;

trait HasCreditManagement
{
    /**
     * Relationship with customer credit
     */
    public function customerCredit()
    {
        return $this->hasOne(CustomerCredit::class, 'customer_id');
    }
    
    /**
     * Relationship with payment terms
     */
    public function paymentTerms()
    {
        return $this->hasMany(PaymentTerm::class, 'customer_id');
    }
    
    /**
     * Get pending payment terms
     */
    public function pendingPaymentTerms()
    {
        return $this->paymentTerms()
            ->whereIn('status', ['pending', 'partial']);
    }
    
    /**
     * Get overdue payment terms
     */
    public function overduePaymentTerms()
    {
        return $this->paymentTerms()
            ->where('status', 'overdue');
    }
    
    /**
     * Get comprehensive credit information
     */
    public function getCreditInfo(): array
    {
        $creditValidator = app(CreditValidator::class);
        return $creditValidator->getCustomerCreditInfo($this->id);
    }
    
    /**
     * Check if customer has sufficient credit for amount
     */
    public function hasSufficientCredit(float $amount): bool
    {
        $creditValidator = app(CreditValidator::class);
        $validation = $creditValidator->validateCreditLimit($this->id, $amount);
        
        return $validation['approved'];
    }
    
    /**
     * Get available credit amount
     */
    public function getAvailableCredit(): float
    {
        $creditInfo = $this->getCreditInfo();
        return $creditInfo['available_credit'];
    }
    
    /**
     * Get current debt amount
     */
    public function getCurrentDebt(): float
    {
        $creditInfo = $this->getCreditInfo();
        return $creditInfo['current_debt'];
    }
    
    /**
     * Get credit utilization percentage
     */
    public function getCreditUtilization(): float
    {
        $creditInfo = $this->getCreditInfo();
        return $creditInfo['credit_utilization_percent'];
    }
    
    /**
     * Check if customer has overdue payments
     */
    public function hasOverduePayments(): bool
    {
        return $this->overduePaymentTerms()->exists();
    }
    
    /**
     * Get total overdue amount
     */
    public function getOverdueAmount(): float
    {
        $creditValidator = app(CreditValidator::class);
        return $creditValidator->getOverdueAmount($this->id);
    }
    
    /**
     * Get credit status
     */
    public function getCreditStatus(): string
    {
        if (!$this->customerCredit || !$this->customerCredit->is_active) {
            return 'no_credit';
        }
        
        if ($this->hasOverduePayments()) {
            return 'overdue';
        }
        
        $utilization = $this->getCreditUtilization();
        
        if ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'high';
        } elseif ($utilization >= 50) {
            return 'moderate';
        } else {
            return 'good';
        }
    }
    
    /**
     * Get credit status label
     */
    public function getCreditStatusLabel(): string
    {
        return match($this->getCreditStatus()) {
            'no_credit' => 'No Credit Facility',
            'overdue' => 'Overdue Payments',
            'critical' => 'Critical (90%+ Used)',
            'high' => 'High Usage (75%+)',
            'moderate' => 'Moderate Usage (50%+)',
            'good' => 'Good Standing',
            default => 'Unknown'
        };
    }
    
    /**
     * Get credit status color for UI
     */
    public function getCreditStatusColor(): string
    {
        return match($this->getCreditStatus()) {
            'no_credit' => 'secondary',
            'overdue' => 'danger',
            'critical' => 'danger',
            'high' => 'warning',
            'moderate' => 'info',
            'good' => 'success',
            default => 'secondary'
        };
    }
    
    /**
     * Update credit limit
     */
    public function updateCreditLimit(float $newLimit, ?string $reason = null): bool
    {
        $creditValidator = app(CreditValidator::class);
        return $creditValidator->updateCreditLimit($this->id, $newLimit, $reason);
    }
    
    /**
     * Block/unblock credit facility
     */
    public function toggleCreditStatus(bool $isActive, ?string $reason = null): bool
    {
        $creditValidator = app(CreditValidator::class);
        return $creditValidator->toggleCreditStatus($this->id, $isActive, $reason);
    }
    
    /**
     * Record payment for a payment term
     */
    public function recordPayment(
        int $paymentTermId, 
        float $amount, 
        ?string $method = null,
        ?string $reference = null
    ): bool {
        $creditValidator = app(CreditValidator::class);
        return $creditValidator->recordPayment($paymentTermId, $amount, $method, $reference);
    }
    
    /**
     * Refresh credit calculation
     */
    public function refreshCredit(): void
    {
        $creditValidator = app(CreditValidator::class);
        $creditValidator->refreshCustomerCredit($this->id);
    }
    
    /**
     * Check if customer qualifies for tempo payment
     */
    public function canUseTempoPayment(): bool
    {
        return $this->customerCredit 
            && $this->customerCredit->is_active 
            && $this->customerCredit->credit_limit > 0
            && !$this->hasOverduePayments();
    }
    
    /**
     * Get payment term days for this customer
     */
    public function getPaymentTermDays(): int
    {
        return $this->payment_term_days ?? 0;
    }
    
    /**
     * Scope for customers with credit facility
     */
    public function scopeHasCredit($query)
    {
        return $query->whereHas('customerCredit', function ($q) {
            $q->where('is_active', true)
              ->where('credit_limit', '>', 0);
        });
    }
    
    /**
     * Scope for customers with overdue payments
     */
    public function scopeHasOverdue($query)
    {
        return $query->whereHas('paymentTerms', function ($q) {
            $q->where('status', 'overdue');
        });
    }
    
    /**
     * Scope for customers with high credit utilization
     */
    public function scopeHighCreditUsage($query, float $threshold = 75.0)
    {
        return $query->whereHas('customerCredit', function ($q) use ($threshold) {
            $q->whereRaw('(current_debt / credit_limit * 100) >= ?', [$threshold]);
        });
    }
}
