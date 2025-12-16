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
            StockMovementSeeder::class,
            DeliveryNoteSeeder::class,
            // BuildingProductSeeder::class, // Disabled to avoid conflicts
            
            // Price levels (depends on products)
            PriceLevelSeeder::class,
            

        ]);
    }
}