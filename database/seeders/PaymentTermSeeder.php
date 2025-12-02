<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentTerm;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class PaymentTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get orders from business customers (wholesale, contractor, distributor)
        $businessOrders = Order::whereHas('user', function($query) {
            $query->whereIn('customer_type', ['wholesale', 'contractor', 'distributor']);
        })->limit(20)->get();
        
        foreach ($businessOrders as $order) {
            if (rand(0, 2) === 0) { // 33% chance this order has payment terms (tempo)
                $this->createPaymentTerm($order);
            }
        }
        
        // Create specific test scenarios
        $this->createTestScenarios();
    }
    
    /**
     * Create payment term for an order
     */
    private function createPaymentTerm(Order $order): void
    {
        $customer = $order->user;
        $paymentTermDays = $this->getPaymentTermDays($customer->customer_type);
        $dueDate = $order->created_at->addDays($paymentTermDays);
        $status = $this->determinePaymentStatus($dueDate);
        
        $amount = $order->grand_total;
        $paidAmount = $this->calculatePaidAmount($status, $amount);
        $paymentDate = $status === 'paid' ? $this->getPaymentDate($dueDate) : null;
        
        PaymentTerm::create([
            'order_id' => $order->id,
            'customer_id' => $order->user_id,
            'due_date' => $dueDate,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'payment_date' => $paymentDate,
            'notes' => $this->generatePaymentNotes($status, $customer->customer_type),
            'created_at' => $order->created_at,
            'updated_at' => $paymentDate ?? $order->updated_at
        ]);
    }
    
    /**
     * Get payment term days based on customer type
     */
    private function getPaymentTermDays(string $customerType): int
    {
        return match($customerType) {
            'wholesale' => rand(0, 1) ? 30 : 14, // Mostly 30 days, some 14 days
            'contractor' => rand(0, 1) ? 45 : 30, // Mostly 45 days, some 30 days  
            'distributor' => rand(0, 1) ? 60 : 45, // Mostly 60 days, some 45 days
            default => 30
        };
    }
    
    /**
     * Determine payment status based on due date
     */
    private function determinePaymentStatus(Carbon $dueDate): string
    {
        $now = Carbon::now();
        $daysDiff = $now->diffInDays($dueDate, false);
        
        if ($daysDiff < -7) {
            // Overdue for more than 7 days
            return rand(0, 2) === 0 ? 'overdue' : 'paid'; // Some eventually get paid
        } elseif ($daysDiff < 0) {
            // Overdue but less than 7 days
            return rand(0, 1) ? 'overdue' : 'paid';
        } elseif ($daysDiff < 7) {
            // Due soon
            return rand(0, 3) === 0 ? 'paid' : 'pending';
        } else {
            // Still has time
            return rand(0, 5) === 0 ? 'paid' : 'pending'; // Some pay early
        }
    }
    
    /**
     * Calculate paid amount based on status
     */
    private function calculatePaidAmount(string $status, int $amount): int
    {
        return match($status) {
            'paid' => $amount,
            'partial' => rand(30, 80) * $amount / 100, // 30-80% paid
            'pending', 'overdue' => rand(0, 1) ? 0 : rand(10, 30) * $amount / 100, // Some partial payments
            default => 0
        };
    }
    
    /**
     * Get payment date for paid invoices
     */
    private function getPaymentDate(Carbon $dueDate): Carbon
    {
        // Payment could be before, on, or after due date
        $paymentOffset = rand(-5, 10); // -5 days (early) to +10 days (late)
        return $dueDate->copy()->addDays($paymentOffset);
    }
    
    /**
     * Generate payment notes based on status and customer type
     */
    private function generatePaymentNotes(string $status, string $customerType): string
    {
        $customerTypeLabel = match($customerType) {
            'wholesale' => 'Pelanggan Grosir',
            'contractor' => 'Kontraktor',
            'distributor' => 'Distributor',
            default => 'Pelanggan'
        };
        
        return match($status) {
            'pending' => "{$customerTypeLabel} - Menunggu pembayaran tempo.",
            'partial' => "{$customerTypeLabel} - Pembayaran sebagian. Sisa akan dibayar sesuai kesepakatan.",
            'paid' => "{$customerTypeLabel} - Pembayaran lunas. Terima kasih atas kepercayaannya.",
            'overdue' => "{$customerTypeLabel} - JATUH TEMPO. Segera hubungi untuk pelunasan.",
            default => "{$customerTypeLabel} - Tempo pembayaran normal."
        };
    }
    
    /**
     * Create specific test scenarios for payment terms
     */
    private function createTestScenarios(): void
    {
        $businessCustomers = User::whereIn('customer_type', ['wholesale', 'contractor', 'distributor'])
                                ->limit(5)->get();
        
        if ($businessCustomers->count() > 0) {
            // Scenario 1: Overdue payment (high priority)
            $customer1 = $businessCustomers->first();
            $order1 = Order::where('user_id', $customer1->id)->first();
            
            if ($order1) {
                PaymentTerm::create([
                    'order_id' => $order1->id,
                    'customer_id' => $customer1->id,
                    'due_date' => Carbon::now()->subDays(15), // Overdue 15 days
                    'amount' => 50_000_000,
                    'paid_amount' => 0,
                    'status' => 'overdue',
                    'payment_date' => null,
                    'notes' => 'URGENT: Jatuh tempo 15 hari lalu. Sudah dikirimi reminder 3x. Koordinasi segera dengan customer.',
                    'created_at' => Carbon::now()->subDays(45),
                    'updated_at' => Carbon::now()->subDays(15)
                ]);
            }
            
            // Scenario 2: Due today
            if ($businessCustomers->count() > 1) {
                $customer2 = $businessCustomers->skip(1)->first();
                $order2 = Order::where('user_id', $customer2->id)->first();
                
                if ($order2) {
                    PaymentTerm::create([
                        'order_id' => $order2->id,
                        'customer_id' => $customer2->id,
                        'due_date' => Carbon::now(), // Due today
                        'amount' => 75_000_000,
                        'paid_amount' => 0,
                        'status' => 'pending',
                        'payment_date' => null,
                        'notes' => 'Jatuh tempo hari ini. Customer sudah dikonfirmasi via WhatsApp.',
                        'created_at' => Carbon::now()->subDays(30),
                        'updated_at' => Carbon::now()->subDays(30)
                    ]);
                }
            }
            
            // Scenario 3: Partial payment
            if ($businessCustomers->count() > 2) {
                $customer3 = $businessCustomers->skip(2)->first();
                $order3 = Order::where('user_id', $customer3->id)->first();
                
                if ($order3) {
                    $totalAmount = 120_000_000;
                    $partialAmount = 80_000_000;
                    
                    PaymentTerm::create([
                        'order_id' => $order3->id,
                        'customer_id' => $customer3->id,
                        'due_date' => Carbon::now()->addDays(7), // Due in 7 days
                        'amount' => $totalAmount,
                        'paid_amount' => $partialAmount,
                        'status' => 'partial',
                        'payment_date' => Carbon::now()->subDays(3),
                        'notes' => "Pembayaran parsial Rp " . number_format($partialAmount, 0, ',', '.') . 
                                 ". Sisa Rp " . number_format($totalAmount - $partialAmount, 0, ',', '.') . 
                                 " akan dibayar sebelum due date.",
                        'created_at' => Carbon::now()->subDays(23),
                        'updated_at' => Carbon::now()->subDays(3)
                    ]);
                }
            }
            
            // Scenario 4: Paid early (good customer)
            if ($businessCustomers->count() > 3) {
                $customer4 = $businessCustomers->skip(3)->first();
                $order4 = Order::where('user_id', $customer4->id)->first();
                
                if ($order4) {
                    $amount = 95_000_000;
                    
                    PaymentTerm::create([
                        'order_id' => $order4->id,
                        'customer_id' => $customer4->id,
                        'due_date' => Carbon::now()->addDays(20), // Due in 20 days
                        'amount' => $amount,
                        'paid_amount' => $amount,
                        'status' => 'paid',
                        'payment_date' => Carbon::now()->subDays(2), // Paid early
                        'notes' => 'Excellent! Customer membayar 22 hari sebelum due date. Track record pembayaran sangat baik.',
                        'created_at' => Carbon::now()->subDays(25),
                        'updated_at' => Carbon::now()->subDays(2)
                    ]);
                }
            }
            
            // Scenario 5: New tempo (just created)
            if ($businessCustomers->count() > 4) {
                $customer5 = $businessCustomers->skip(4)->first();
                $order5 = Order::where('user_id', $customer5->id)->first();
                
                if ($order5) {
                    PaymentTerm::create([
                        'order_id' => $order5->id,
                        'customer_id' => $customer5->id,
                        'due_date' => Carbon::now()->addDays(45), // Due in 45 days
                        'amount' => 150_000_000,
                        'paid_amount' => 0,
                        'status' => 'pending',
                        'payment_date' => null,
                        'notes' => 'Tempo pembayaran baru. Customer type: Kontraktor. Proyek: Renovasi Gedung ABC.',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }
        }
    }
}
