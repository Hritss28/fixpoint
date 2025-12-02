<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user first
        User::create([
            'name' => 'Admin Toko Bangunan',
            'email' => 'admin@tokobangunan.com',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'customer_type' => 'retail', // Admin as retail type
            'company_name' => null,
            'tax_number' => null,
            'credit_limit' => 0,
            'payment_term_days' => 0,
            'billing_address' => 'Jl. Admin Utama No. 1',
            'shipping_address' => 'Jl. Admin Utama No. 1',
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
        
        // Create retail customers (individual buyers)
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => 'Customer Retail ' . $i,
                'email' => 'retail' . $i . '@example.com',
                'password' => Hash::make('password'),
                'phone' => '0812345678' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'customer_type' => 'retail',
                'company_name' => null,
                'tax_number' => null,
                'credit_limit' => 0,
                'payment_term_days' => 0,
                'billing_address' => 'Jl. Pelanggan Retail No. ' . $i,
                'shipping_address' => 'Jl. Pelanggan Retail No. ' . $i,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }
        
        // Create wholesale customers (toko kecil, pedagang)
        $wholesaleCustomers = [
            [
                'name' => 'Toko Bangunan Sejahtera',
                'email' => 'sejahtera@tokobangunan.com',
                'company_name' => 'CV Sejahtera Abadi',
                'tax_number' => '12.345.678.9-012.345',
                'credit_limit' => 100_000_000,
                'payment_term_days' => 30,
            ],
            [
                'name' => 'UD Maju Bersama',
                'email' => 'majubersama@tokobangunan.com',
                'company_name' => 'UD Maju Bersama',
                'tax_number' => '23.456.789.0-123.456',
                'credit_limit' => 75_000_000,
                'payment_term_days' => 21,
            ],
            [
                'name' => 'Toko Material Jaya',
                'email' => 'materialjaya@tokobangunan.com',
                'company_name' => 'Toko Material Jaya',
                'tax_number' => '34.567.890.1-234.567',
                'credit_limit' => 150_000_000,
                'payment_term_days' => 30,
            ],
            [
                'name' => 'CV Bangunan Mandiri',
                'email' => 'bangunanmandiri@tokobangunan.com',
                'company_name' => 'CV Bangunan Mandiri',
                'tax_number' => '45.678.901.2-345.678',
                'credit_limit' => 200_000_000,
                'payment_term_days' => 30,
            ],
            [
                'name' => 'Toko Sumber Rejeki',
                'email' => 'sumberrejeki@tokobangunan.com',
                'company_name' => 'Toko Sumber Rejeki',
                'tax_number' => '56.789.012.3-456.789',
                'credit_limit' => 80_000_000,
                'payment_term_days' => 14,
            ],
        ];
        
        foreach ($wholesaleCustomers as $i => $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password'),
                'phone' => '0215678901' . $i,
                'customer_type' => 'wholesale',
                'company_name' => $customer['company_name'],
                'tax_number' => $customer['tax_number'],
                'credit_limit' => $customer['credit_limit'],
                'payment_term_days' => $customer['payment_term_days'],
                'billing_address' => 'Jl. Billing Wholesale No. ' . ($i + 1) . ', Jakarta',
                'shipping_address' => 'Jl. Shipping Wholesale No. ' . ($i + 1) . ', Jakarta',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }
        
        // Create contractor customers (kontraktor konstruksi)
        $contractorCustomers = [
            [
                'name' => 'PT Konstruksi Prima',
                'email' => 'konstruksiprima@contractor.com',
                'company_name' => 'PT Konstruksi Prima',
                'tax_number' => '01.234.567.8-901.234',
                'credit_limit' => 500_000_000,
                'payment_term_days' => 45,
            ],
            [
                'name' => 'CV Bangun Indah',
                'email' => 'bangunindah@contractor.com',
                'company_name' => 'CV Bangun Indah',
                'tax_number' => '12.345.678.9-012.345',
                'credit_limit' => 300_000_000,
                'payment_term_days' => 30,
            ],
            [
                'name' => 'PT Cipta Karya',
                'email' => 'ciptakarya@contractor.com',
                'company_name' => 'PT Cipta Karya',
                'tax_number' => '23.456.789.0-123.456',
                'credit_limit' => 750_000_000,
                'payment_term_days' => 60,
            ],
            [
                'name' => 'Kontraktor Jaya Abadi',
                'email' => 'jayaabadi@contractor.com',
                'company_name' => 'CV Jaya Abadi Konstruksi',
                'tax_number' => '34.567.890.1-234.567',
                'credit_limit' => 400_000_000,
                'payment_term_days' => 45,
            ],
        ];
        
        foreach ($contractorCustomers as $i => $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password'),
                'phone' => '0216789012' . $i,
                'customer_type' => 'contractor',
                'company_name' => $customer['company_name'],
                'tax_number' => $customer['tax_number'],
                'credit_limit' => $customer['credit_limit'],
                'payment_term_days' => $customer['payment_term_days'],
                'billing_address' => 'Jl. Office Kontraktor No. ' . ($i + 1) . ', Jakarta',
                'shipping_address' => 'Jl. Site Project No. ' . ($i + 1) . ', Jakarta',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }
        
        // Create distributor customers (distributor besar)
        $distributorCustomers = [
            [
                'name' => 'PT Distributor Nasional',
                'email' => 'nasional@distributor.com',
                'company_name' => 'PT Distributor Nasional',
                'tax_number' => '01.111.222.3-333.444',
                'credit_limit' => 1_000_000_000,
                'payment_term_days' => 60,
            ],
            [
                'name' => 'CV Mega Distribusi',
                'email' => 'megadistribusi@distributor.com',
                'company_name' => 'CV Mega Distribusi',
                'tax_number' => '02.222.333.4-444.555',
                'credit_limit' => 800_000_000,
                'payment_term_days' => 45,
            ],
            [
                'name' => 'PT Indo Supplier',
                'email' => 'indosupplier@distributor.com',
                'company_name' => 'PT Indo Supplier Tbk',
                'tax_number' => '03.333.444.5-555.666',
                'credit_limit' => 1_500_000_000,
                'payment_term_days' => 90,
            ],
        ];
        
        foreach ($distributorCustomers as $i => $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password'),
                'phone' => '0217890123' . $i,
                'customer_type' => 'distributor',
                'company_name' => $customer['company_name'],
                'tax_number' => $customer['tax_number'],
                'credit_limit' => $customer['credit_limit'],
                'payment_term_days' => $customer['payment_term_days'],
                'billing_address' => 'Jl. HO Distributor No. ' . ($i + 1) . ', Jakarta',
                'shipping_address' => 'Jl. Warehouse No. ' . ($i + 1) . ', Jakarta',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
