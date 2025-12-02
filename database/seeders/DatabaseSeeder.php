<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Basic data first
            BrandSeeder::class,
            CategorySeeder::class,
            
            // Users need to be created before suppliers and customer credits
            UserSeeder::class,
            
            // Suppliers (needed before products get supplier assignments)
            SupplierSeeder::class,
            
            // Products (enhanced with building store data)
            ProductSeeder::class, // Building materials products
            // BuildingProductSeeder::class, // Disabled to avoid conflicts
            
            // Price levels (depends on products)
            PriceLevelSeeder::class,
            
            // Customer credits (depends on users with customer_type)
            CustomerCreditSeeder::class,
            
            // Stock movements (depends on products)
            StockMovementSeeder::class,
            
            // Orders and related data
            // Note: Make sure OrderSeeder creates orders with different customer types
            
            // Delivery notes and payment terms (depend on orders)
            DeliveryNoteSeeder::class,
            PaymentTermSeeder::class,
        ]);
    }
}