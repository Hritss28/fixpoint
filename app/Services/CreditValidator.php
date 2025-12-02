<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\CustomerCredit;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreditValidator
{
    /**
     * Validate if customer has sufficient credit for order amount
     * 
     * @param int $customerId
     * @param float $orderAmount
     * @return array
     */
    public function validateCreditLimit(int $customerId, float $orderAmount): array
    {
        $customer = User::findOrFail($customerId);
        $creditInfo = $this->getCustomerCreditInfo($customerId);
        
        $availableCredit = $creditInfo['available_credit'];
        $creditLimit = $creditInfo['credit_limit'];
        
        // Check if customer has credit facility
        if ($creditLimit <= 0) {
            return [
                'approved' => false,
                'reason' => 'Customer does not have credit facility',
                'available_credit' => 0,
                'required_amount' => $orderAmount,
                'shortfall' => $orderAmount,
            ];
        }
        
        // Check if sufficient credit available
        if ($availableCredit < $orderAmount) {
            return [
                'approved' => false,
                'reason' => 'Insufficient credit limit',
                'available_credit' => $availableCredit,
                'required_amount' => $orderAmount,
                'shortfall' => $orderAmount - $availableCredit,
            ];
        }
        
        // Check for overdue payments
        $overdueAmount = $this->getOverdueAmount($customerId);
        if ($overdueAmount > 0) {
            return [
                'approved' => false,
                'reason' => 'Customer has overdue payments',
                'available_credit' => $availableCredit,
                'required_amount' => $orderAmount,
                'overdue_amount' => $overdueAmount,
                'shortfall' => 0,
            ];
        }
        
        Log::info("Credit approved for customer {$customerId}: Amount {$orderAmount}, Available {$availableCredit}");
        
        return [
            'approved' => true,
            'reason' => 'Credit limit sufficient',
            'available_credit' => $availableCredit,
            'required_amount' => $orderAmount,
            'remaining_after_order' => $availableCredit - $orderAmount,
            'shortfall' => 0,
        ];
    }
    
    /**
     * Get comprehensive customer credit information
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerCreditInfo(int $customerId): array
    {
        $customer = User::findOrFail($customerId);
        
        // Get or create customer credit record
        $customerCredit = CustomerCredit::firstOrCreate(
            ['customer_id' => $customerId],
            [
                'credit_limit' => $customer->credit_limit ?? 0,
                'current_debt' => 0,
                'available_credit' => $customer->credit_limit ?? 0,
                'is_active' => true,
            ]
        );
        
        // Calculate current debt from active payment terms
        $currentDebt = $this->calculateCurrentDebt($customerId);
        
        // Update customer credit with latest debt calculation
        $availableCredit = max(0, $customerCredit->credit_limit - $currentDebt);
        
        $customerCredit->update([
            'current_debt' => $currentDebt,
            'available_credit' => $availableCredit,
        ]);
        
        // Get payment history
        $overdueAmount = $this->getOverdueAmount($customerId);
        $totalOrders = $this->getTotalOrdersCount($customerId);
        $creditUtilization = $customerCredit->credit_limit > 0 ? 
            ($currentDebt / $customerCredit->credit_limit) * 100 : 0;
        
        return [
            'customer_id' => $customerId,
            'customer_name' => $customer->name,
            'customer_type' => $customer->customer_type ?? 'retail',
            'credit_limit' => $customerCredit->credit_limit,
            'current_debt' => $currentDebt,
            'available_credit' => $availableCredit,
            'overdue_amount' => $overdueAmount,
            'credit_utilization_percent' => round($creditUtilization, 2),
            'is_active' => $customerCredit->is_active,
            'total_orders' => $totalOrders,
            'payment_terms_days' => $customer->payment_term_days ?? 0,
            'last_payment_date' => $this->getLastPaymentDate($customerId),
        ];
    }
    
    /**
     * Calculate current debt from unpaid orders
     * 
     * @param int $customerId
     * @return float
     */
    private function calculateCurrentDebt(int $customerId): float
    {
        return PaymentTerm::where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(DB::raw('amount - paid_amount'));
    }
    
    /**
     * Get overdue payment amount
     * 
     * @param int $customerId
     * @return float
     */
    public function getOverdueAmount(int $customerId): float
    {
        return PaymentTerm::where('customer_id', $customerId)
            ->where('status', 'overdue')
            ->sum(DB::raw('amount - paid_amount'));
    }
    
    /**
     * Get last payment date
     * 
     * @param int $customerId
     * @return Carbon|null
     */
    private function getLastPaymentDate(int $customerId): ?Carbon
    {
        $lastPayment = PaymentTerm::where('customer_id', $customerId)
            ->where('status', 'paid')
            ->latest('payment_date')
            ->first();
            
        return $lastPayment ? $lastPayment->payment_date : null;
    }
    
    /**
     * Get total orders count
     * 
     * @param int $customerId
     * @return int
     */
    private function getTotalOrdersCount(int $customerId): int
    {
        return Order::where('user_id', $customerId)->count();
    }
    
    /**
     * Update customer credit limit
     * 
     * @param int $customerId
     * @param float $newCreditLimit
     * @param string|null $reason
     * @param int|null $approvedBy
     * @return bool
     */
    public function updateCreditLimit(
        int $customerId, 
        float $newCreditLimit, 
        ?string $reason = null,
        ?int $approvedBy = null
    ): bool {
        return DB::transaction(function () use ($customerId, $newCreditLimit, $reason, $approvedBy) {
            $customerCredit = CustomerCredit::where('customer_id', $customerId)->first();
            
            if (!$customerCredit) {
                $customerCredit = CustomerCredit::create([
                    'customer_id' => $customerId,
                    'credit_limit' => $newCreditLimit,
                    'current_debt' => 0,
                    'available_credit' => $newCreditLimit,
                    'is_active' => true,
                ]);
            } else {
                $oldLimit = $customerCredit->credit_limit;
                
                // Update credit limit
                $customerCredit->update([
                    'credit_limit' => $newCreditLimit,
                    'available_credit' => max(0, $newCreditLimit - $customerCredit->current_debt),
                ]);
                
                Log::info("Credit limit updated for customer {$customerId}: {$oldLimit} -> {$newCreditLimit}");
            }
            
            // Also update user table
            User::where('id', $customerId)->update(['credit_limit' => $newCreditLimit]);
            
            return true;
        });
    }
    
    /**
     * Block/unblock customer credit
     * 
     * @param int $customerId
     * @param bool $isActive
     * @param string|null $reason
     * @return bool
     */
    public function toggleCreditStatus(int $customerId, bool $isActive, ?string $reason = null): bool
    {
        $customerCredit = CustomerCredit::where('customer_id', $customerId)->first();
        
        if ($customerCredit) {
            $customerCredit->update([
                'is_active' => $isActive,
                'notes' => $reason,
            ]);
            
            Log::info("Credit status changed for customer {$customerId}: " . ($isActive ? 'ACTIVE' : 'BLOCKED'));
            return true;
        }
        
        return false;
    }
    
    /**
     * Record payment and update credit
     * 
     * @param int $paymentTermId
     * @param float $paidAmount
     * @param string|null $paymentMethod
     * @param string|null $paymentReference
     * @return bool
     */
    public function recordPayment(
        int $paymentTermId, 
        float $paidAmount, 
        ?string $paymentMethod = null,
        ?string $paymentReference = null
    ): bool {
        return DB::transaction(function () use ($paymentTermId, $paidAmount, $paymentMethod, $paymentReference) {
            $paymentTerm = PaymentTerm::findOrFail($paymentTermId);
            
            $remainingAmount = $paymentTerm->amount - $paymentTerm->paid_amount;
            
            if ($paidAmount > $remainingAmount) {
                throw new \Exception("Payment amount ({$paidAmount}) exceeds remaining balance ({$remainingAmount})");
            }
            
            // Update payment term
            $newPaidAmount = $paymentTerm->paid_amount + $paidAmount;
            $newStatus = $newPaidAmount >= $paymentTerm->amount ? 'paid' : 'partial';
            
            $paymentTerm->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'payment_date' => now(),
            ]);
            
            // Update customer credit
            $this->refreshCustomerCredit($paymentTerm->customer_id);
            
            Log::info("Payment recorded: PaymentTerm {$paymentTermId}, Amount {$paidAmount}, Status {$newStatus}");
            
            return true;
        });
    }
    
    /**
     * Refresh customer credit calculation
     * 
     * @param int $customerId
     * @return void
     */
    public function refreshCustomerCredit(int $customerId): void
    {
        $currentDebt = $this->calculateCurrentDebt($customerId);
        
        $customerCredit = CustomerCredit::where('customer_id', $customerId)->first();
        
        if ($customerCredit) {
            $availableCredit = max(0, $customerCredit->credit_limit - $currentDebt);
            
            $customerCredit->update([
                'current_debt' => $currentDebt,
                'available_credit' => $availableCredit,
            ]);
        }
    }
    
    /**
     * Get customers with overdue payments
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersWithOverduePayments()
    {
        return User::whereHas('paymentTerms', function ($query) {
                $query->where('status', 'overdue');
            })
            ->with(['paymentTerms' => function ($query) {
                $query->where('status', 'overdue');
            }])
            ->get();
    }
    
    /**
     * Mark overdue payments
     * 
     * @return int Number of payments marked as overdue
     */
    public function markOverduePayments(): int
    {
        $overdueCount = PaymentTerm::where('due_date', '<', now())
            ->where('status', 'pending')
            ->update(['status' => 'overdue']);
            
        Log::info("Marked {$overdueCount} payments as overdue");
        
        return $overdueCount;
    }
    
    /**
     * Get aging report for receivables
     * 
     * @return array
     */
    public function getAgingReport(): array
    {
        $agingRanges = [
            'current' => ['min' => 0, 'max' => 0],
            '1_30_days' => ['min' => 1, 'max' => 30],
            '31_60_days' => ['min' => 31, 'max' => 60],
            '61_90_days' => ['min' => 61, 'max' => 90],
            'over_90_days' => ['min' => 91, 'max' => 9999],
        ];
        
        $report = [];
        $totalAmount = 0;
        
        foreach ($agingRanges as $range => $days) {
            $startDate = $days['min'] == 0 ? now() : now()->subDays($days['max']);
            $endDate = $days['max'] == 9999 ? now()->subYears(10) : now()->subDays($days['min']);
            
            $amount = PaymentTerm::whereIn('status', ['pending', 'partial', 'overdue'])
                ->when($days['min'] == 0, function ($query) {
                    return $query->where('due_date', '>=', now());
                })
                ->when($days['min'] > 0, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('due_date', [$endDate, $startDate]);
                })
                ->sum(DB::raw('amount - paid_amount'));
            
            $report[$range] = $amount;
            $totalAmount += $amount;
        }
        
        $report['total'] = $totalAmount;
        
        return $report;
    }
}
