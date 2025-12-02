<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        
        foreach ($products as $product) {
            // Create initial stock in (when product was first added)
            $this->createInitialStock($product);
            
            // Create some purchase/stock in movements
            $this->createPurchaseMovements($product);
            
            // Create some adjustment movements
            $this->createAdjustmentMovements($product);
            
            // Create sales/stock out movements (based on existing orders)
            $this->createSalesMovements($product);
        }
        
        // Create recent movements for testing
        $this->createRecentMovements();
    }
    
    /**
     * Create initial stock when product was added
     */
    private function createInitialStock(Product $product): void
    {
        $initialQty = rand(100, 500);
        $unit = $product->unit ?? 'pcs';
        
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $initialQty,
            'unit' => $unit,
            'reference_type' => 'initial',
            'reference_id' => null,
            'notes' => 'Stock awal saat produk ditambahkan ke sistem',
            'user_id' => 1, // Admin user
            'created_at' => $product->created_at,
            'updated_at' => $product->created_at
        ]);
        
        // Note: We'll track stock through StockMovement, not product.current_stock
    }
    
    /**
     * Create purchase/stock in movements
     */
    private function createPurchaseMovements(Product $product): void
    {
        $movementCount = rand(3, 8);
        
        for ($i = 0; $i < $movementCount; $i++) {
            $date = Carbon::now()->subDays(rand(5, 90));
            $quantity = rand(50, 200);
            $unit = $product->unit ?? 'pcs';
            
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit' => $unit,
                'reference_type' => 'purchase',
                'reference_id' => rand(1000, 9999), // Fake purchase ID
                'notes' => $this->generatePurchaseNotes($product),
                'user_id' => 1,
                'created_at' => $date,
                'updated_at' => $date
            ]);
            
            // Note: Stock tracking through StockMovement model
        }
    }
    
    /**
     * Create adjustment movements (plus/minus for various reasons)
     */
    private function createAdjustmentMovements(Product $product): void
    {
        $adjustmentCount = rand(1, 3);
        
        for ($i = 0; $i < $adjustmentCount; $i++) {
            $date = Carbon::now()->subDays(rand(10, 60));
            $isPositive = rand(0, 1);
            $quantity = $isPositive ? rand(5, 50) : -rand(5, 30);
            $unit = $product->unit ?? 'pcs';
            
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'unit' => $unit,
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'notes' => $this->generateAdjustmentNotes($quantity),
                'user_id' => 1,
                'created_at' => $date,
                'updated_at' => $date
            ]);
            
            // Note: Stock tracking through StockMovement model
        }
    }
    
    /**
     * Create sales/stock out movements based on existing orders
     */
    private function createSalesMovements(Product $product): void
    {
        // Get some orders that might contain this product
        $orders = Order::inRandomOrder()->limit(rand(2, 5))->get();
        
        foreach ($orders as $order) {
            if (rand(0, 1)) { // 50% chance this product was in the order
                $quantity = rand(1, 20);
                $unit = $product->unit ?? 'pcs';
                
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'notes' => "Penjualan ke {$order->name} - Order #{$order->id}",
                    'user_id' => $order->user_id,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at
                ]);
                
                // Note: Stock tracking through StockMovement model
            }
        }
    }
    
    /**
     * Create recent movements for testing current functionality
     */
    private function createRecentMovements(): void
    {
        $recentProducts = Product::inRandomOrder()->limit(10)->get();
        
        foreach ($recentProducts as $product) {
            // Recent stock in (yesterday)
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => rand(20, 100),
                'unit' => $product->unit ?? 'pcs',
                'reference_type' => 'purchase',
                'reference_id' => rand(10000, 99999),
                'notes' => 'Pembelian terbaru dari ' . $product->supplier?->name ?? 'Supplier',
                'user_id' => 1,
                'created_at' => Carbon::yesterday(),
                'updated_at' => Carbon::yesterday()
            ]);
            
            // Recent stock out (today)
            if (rand(0, 1)) {
                $outQty = rand(5, 25);
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $outQty,
                    'unit' => $product->unit ?? 'pcs',
                    'reference_type' => 'order',
                    'reference_id' => rand(1000, 9999),
                    'notes' => 'Penjualan hari ini',
                    'user_id' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                
                // Note: Stock tracking through StockMovement model
            }
        }
    }
    
    /**
     * Generate realistic purchase notes
     */
    private function generatePurchaseNotes(Product $product): string
    {
        $suppliers = [
            'PT Semen Indonesia',
            'Krakatau Steel', 
            'Holcim Indonesia',
            'Mulia Ceramics',
            'Besi Jaya',
            'Avian Brands',
            'Wavin Indonesia'
        ];
        
        $supplier = $suppliers[array_rand($suppliers)];
        $poNumber = 'PO-' . rand(1000, 9999);
        
        return "Pembelian dari {$supplier} - {$poNumber}";
    }
    
    /**
     * Generate adjustment notes
     */
    private function generateAdjustmentNotes(int $quantity): string
    {
        if ($quantity > 0) {
            $reasons = [
                'Koreksi stok fisik - kelebihan',
                'Return dari customer - barang tidak sesuai',
                'Temuan stok di gudang',
                'Koreksi input sebelumnya'
            ];
        } else {
            $reasons = [
                'Koreksi stok fisik - kekurangan', 
                'Barang rusak/cacat',
                'Kehilangan/pencurian',
                'Expired/kadaluarsa',
                'Koreksi input sebelumnya'
            ];
        }
        
        return $reasons[array_rand($reasons)];
    }
}
