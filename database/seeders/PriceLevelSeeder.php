<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PriceLevel;
use App\Models\Product;

class PriceLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all products to create price levels
        $products = Product::all();
        
        foreach ($products as $product) {
            $basePrice = $product->price;
            
            // Create price levels for each product
            $priceLevels = [
                [
                    'product_id' => $product->id,
                    'level_type' => 'retail',
                    'min_quantity' => 1,
                    'price' => $basePrice, // Full price for retail
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'wholesale',
                    'min_quantity' => 10,
                    'price' => round($basePrice * 0.90, 0), // 10% discount for wholesale
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'contractor',
                    'min_quantity' => 50,
                    'price' => round($basePrice * 0.85, 0), // 15% discount for contractor
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'distributor',
                    'min_quantity' => 100,
                    'price' => round($basePrice * 0.80, 0), // 20% discount for distributor
                    'is_active' => true
                ]
            ];
            
            foreach ($priceLevels as $priceLevel) {
                PriceLevel::create($priceLevel);
            }
        }
        
        // Special pricing for specific products (building materials)
        $this->createSpecialPricing();
    }
    
    /**
     * Create special pricing for specific building material products
     */
    private function createSpecialPricing(): void
    {
        // Example: Semen has different tier structure
        $semenProducts = Product::where('name', 'LIKE', '%Semen%')->get();
        
        foreach ($semenProducts as $product) {
            $basePrice = $product->price;
            
            // Override default price levels for semen
            PriceLevel::where('product_id', $product->id)->delete();
            
            $semenPriceLevels = [
                [
                    'product_id' => $product->id,
                    'level_type' => 'retail',
                    'min_quantity' => 1,
                    'price' => $basePrice,
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'wholesale',
                    'min_quantity' => 20, // 20 sak minimum for wholesale
                    'price' => round($basePrice * 0.92, 0), // 8% discount
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'contractor',
                    'min_quantity' => 100, // 100 sak minimum for contractor
                    'price' => round($basePrice * 0.88, 0), // 12% discount
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'distributor',
                    'min_quantity' => 500, // 500 sak minimum for distributor
                    'price' => round($basePrice * 0.85, 0), // 15% discount
                    'is_active' => true
                ]
            ];
            
            foreach ($semenPriceLevels as $priceLevel) {
                PriceLevel::create($priceLevel);
            }
        }
        
        // Example: Besi beton has volume-based pricing
        $besiProducts = Product::where('name', 'LIKE', '%Besi%')->get();
        
        foreach ($besiProducts as $product) {
            $basePrice = $product->price;
            
            // Override default price levels for besi
            PriceLevel::where('product_id', $product->id)->delete();
            
            $besiPriceLevels = [
                [
                    'product_id' => $product->id,
                    'level_type' => 'retail',
                    'min_quantity' => 1,
                    'price' => $basePrice,
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'wholesale',
                    'min_quantity' => 5, // 5 batang minimum
                    'price' => round($basePrice * 0.93, 0), // 7% discount
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'contractor',
                    'min_quantity' => 25, // 25 batang minimum
                    'price' => round($basePrice * 0.90, 0), // 10% discount
                    'is_active' => true
                ],
                [
                    'product_id' => $product->id,
                    'level_type' => 'distributor',
                    'min_quantity' => 100, // 100 batang minimum
                    'price' => round($basePrice * 0.87, 0), // 13% discount
                    'is_active' => true
                ]
            ];
            
            foreach ($besiPriceLevels as $priceLevel) {
                PriceLevel::create($priceLevel);
            }
        }
    }
}
