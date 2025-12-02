<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerCredit;
use App\Models\User;

class CustomerCreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users who are wholesale, contractor, or distributor
        $businessCustomers = User::whereIn('customer_type', ['wholesale', 'contractor', 'distributor'])
                                ->get();
        
        foreach ($businessCustomers as $customer) {
            $creditLimit = $this->calculateCreditLimit($customer->customer_type);
            $currentDebt = $this->generateRandomDebt($creditLimit);
            
            CustomerCredit::create([
                'customer_id' => $customer->id,
                'credit_limit' => $creditLimit,
                'current_debt' => $currentDebt,
                'available_credit' => $creditLimit - $currentDebt,
                'is_active' => true,
                'notes' => $this->generateCreditNotes($customer->customer_type, $creditLimit)
            ]);
        }
        
        // Create some specific customer credit scenarios for testing
        $this->createTestScenarios();
    }
    
    /**
     * Calculate credit limit based on customer type
     */
    private function calculateCreditLimit(string $customerType): int
    {
        return match($customerType) {
            'wholesale' => rand(50_000_000, 200_000_000), // 50-200 juta
            'contractor' => rand(100_000_000, 500_000_000), // 100-500 juta
            'distributor' => rand(500_000_000, 1_000_000_000), // 500 juta - 1 miliar
            default => 0
        };
    }
    
    /**
     * Generate random debt (0-70% of credit limit)
     */
    private function generateRandomDebt(int $creditLimit): int
    {
        $maxDebtPercentage = rand(0, 70);
        return round($creditLimit * ($maxDebtPercentage / 100));
    }
    
    /**
     * Generate credit notes based on customer type
     */
    private function generateCreditNotes(string $customerType, int $creditLimit): string
    {
        $limitFormatted = 'Rp ' . number_format($creditLimit, 0, ',', '.');
        
        return match($customerType) {
            'wholesale' => "Limit kredit {$limitFormatted}. Tempo maksimal 30 hari. Review setiap 6 bulan.",
            'contractor' => "Limit kredit {$limitFormatted}. Tempo maksimal 45 hari. Khusus proyek dengan kontrak.",
            'distributor' => "Limit kredit {$limitFormatted}. Tempo maksimal 60 hari. Partner strategis dengan volume tinggi.",
            default => "Limit kredit {$limitFormatted}."
        };
    }
    
    /**
     * Create specific test scenarios
     */
    private function createTestScenarios(): void
    {
        // Scenario 1: Customer dengan limit hampir habis
        $customer1 = User::where('customer_type', 'wholesale')->first();
        if ($customer1) {
            CustomerCredit::updateOrCreate(
                ['customer_id' => $customer1->id],
                [
                    'credit_limit' => 100_000_000,
                    'current_debt' => 95_000_000, // 95% used
                    'available_credit' => 5_000_000,
                    'is_active' => true,
                    'notes' => 'PERHATIAN: Limit kredit hampir habis. Monitoring ketat pembayaran.'
                ]
            );
        }
        
        // Scenario 2: Customer dengan kredit bermasalah
        $customer2 = User::where('customer_type', 'contractor')->first();
        if ($customer2) {
            CustomerCredit::updateOrCreate(
                ['customer_id' => $customer2->id],
                [
                    'credit_limit' => 200_000_000,
                    'current_debt' => 220_000_000, // Over limit
                    'available_credit' => -20_000_000,
                    'is_active' => false, // Credit blocked
                    'notes' => 'KREDIT DIBLOKIR: Melebihi limit. Segera lakukan pelunasan sebelum transaksi baru.'
                ]
            );
        }
        
        // Scenario 3: Customer dengan track record baik
        $customer3 = User::where('customer_type', 'distributor')->first();
        if ($customer3) {
            CustomerCredit::updateOrCreate(
                ['customer_id' => $customer3->id],
                [
                    'credit_limit' => 800_000_000,
                    'current_debt' => 200_000_000, // Only 25% used
                    'available_credit' => 600_000_000,
                    'is_active' => true,
                    'notes' => 'CUSTOMER PREMIUM: Track record pembayaran excellent. Eligible untuk peningkatan limit.'
                ]
            );
        }
        
        // Scenario 4: Customer baru dengan limit kecil
        $customer4 = User::where('customer_type', 'wholesale')->skip(1)->first();
        if ($customer4) {
            CustomerCredit::updateOrCreate(
                ['customer_id' => $customer4->id],
                [
                    'credit_limit' => 25_000_000,
                    'current_debt' => 0, // No debt yet
                    'available_credit' => 25_000_000,
                    'is_active' => true,
                    'notes' => 'CUSTOMER BARU: Limit awal kecil. Review setelah 3 bulan untuk peningkatan.'
                ]
            );
        }
    }
}
