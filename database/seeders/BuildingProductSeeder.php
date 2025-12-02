<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Brand;

class BuildingProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get suppliers, categories, and brands
        $suppliers = Supplier::all();
        $categories = Category::all();
        $brands = Brand::all();
        
        // Building material products data
        $buildingProducts = [
            // SEMEN & BETON
            [
                'name' => 'Semen Gresik Portland Composite Cement (PCC) 50kg',
                'price' => 65000,
                'stock' => 500,
                'description' => 'Semen PCC Gresik berkualitas tinggi untuk konstruksi umum. Cocok untuk pondasi, kolom, balok, dan plat.',
                'slug' => 'semen-gresik-pcc-50kg',
                'unit' => 'sak',
                'min_order_qty' => 10,
                'wholesale_price' => 62000,
                'contractor_price' => 60000,
                'reorder_level' => 100,
                'location' => 'Gudang A1-A3',
                'barcode' => '8991102001234',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Semen Indonesia (Persero) Tbk'
            ],
            [
                'name' => 'Semen Holcim ExtraDura 50kg',
                'price' => 67000,
                'stock' => 300,
                'description' => 'Semen dengan kekuatan ekstra untuk konstruksi yang membutuhkan daya tahan tinggi.',
                'slug' => 'semen-holcim-extradura-50kg',
                'unit' => 'sak',
                'min_order_qty' => 10,
                'wholesale_price' => 64000,
                'contractor_price' => 62000,
                'reorder_level' => 50,
                'location' => 'Gudang A4-A6',
                'barcode' => '8991102001235',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Holcim Indonesia Tbk'
            ],
            
            // BESI BETON
            [
                'name' => 'Besi Beton Ulir SNI 10mm x 12m',
                'price' => 85000,
                'stock' => 200,
                'description' => 'Besi beton ulir diameter 10mm panjang 12 meter sesuai standar SNI untuk konstruksi.',
                'slug' => 'besi-beton-ulir-10mm-12m',
                'unit' => 'batang',
                'min_order_qty' => 5,
                'wholesale_price' => 82000,
                'contractor_price' => 79000,
                'reorder_level' => 50,
                'location' => 'Yard B1',
                'barcode' => '8991102002001',
                'is_bulk_only' => true,
                'supplier_name' => 'PT Krakatau Steel (Persero) Tbk'
            ],
            [
                'name' => 'Besi Beton Ulir SNI 12mm x 12m',
                'price' => 120000,
                'stock' => 150,
                'description' => 'Besi beton ulir diameter 12mm untuk struktur yang membutuhkan kekuatan lebih.',
                'slug' => 'besi-beton-ulir-12mm-12m',
                'unit' => 'batang',
                'min_order_qty' => 5,
                'wholesale_price' => 117000,
                'contractor_price' => 114000,
                'reorder_level' => 30,
                'location' => 'Yard B2',
                'barcode' => '8991102002002',
                'is_bulk_only' => true,
                'supplier_name' => 'CV Besi Jaya Mandiri'
            ],
            
            // KERAMIK
            [
                'name' => 'Keramik Lantai Mulia Spectrum 40x40 Abu-abu',
                'price' => 135000,
                'stock' => 80,
                'description' => 'Keramik lantai premium ukuran 40x40 cm warna abu-abu natural. 1 dus = 6 pcs = 0.96 m²',
                'slug' => 'keramik-mulia-spectrum-40x40-abu',
                'unit' => 'dus',
                'min_order_qty' => 5,
                'wholesale_price' => 128000,
                'contractor_price' => 122000,
                'reorder_level' => 20,
                'location' => 'Gudang C1',
                'barcode' => '8991102003001',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Mulia Industrindo Tbk'
            ],
            [
                'name' => 'Keramik Dinding Mulia Carmen 25x40 Putih',
                'price' => 89000,
                'stock' => 60,
                'description' => 'Keramik dinding glossy ukuran 25x40 cm untuk kamar mandi dan dapur. 1 dus = 10 pcs = 1 m²',
                'slug' => 'keramik-mulia-carmen-25x40-putih',
                'unit' => 'dus',
                'min_order_qty' => 3,
                'wholesale_price' => 85000,
                'contractor_price' => 81000,
                'reorder_level' => 15,
                'location' => 'Gudang C2',
                'barcode' => '8991102003002',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Mulia Industrindo Tbk'
            ],
            
            // CAT & FINISHING
            [
                'name' => 'Cat Tembok Avitex Emulsion 5kg Putih',
                'price' => 175000,
                'stock' => 100,
                'description' => 'Cat tembok interior berkualitas tinggi, mudah dibersihkan, daya tutup sempurna.',
                'slug' => 'cat-avitex-emulsion-5kg-putih',
                'unit' => 'kaleng',
                'min_order_qty' => 2,
                'wholesale_price' => 165000,
                'contractor_price' => 158000,
                'reorder_level' => 25,
                'location' => 'Gudang D1',
                'barcode' => '8991102004001',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Avian Brands Tbk'
            ],
            [
                'name' => 'Cat Besi Avian Glitex 1kg Hitam',
                'price' => 95000,
                'stock' => 75,
                'description' => 'Cat besi anti karat dengan daya tahan lama untuk pagar, kanopi, dan konstruksi besi.',
                'slug' => 'cat-besi-glitex-1kg-hitam',
                'unit' => 'kaleng',
                'min_order_qty' => 2,
                'wholesale_price' => 89000,
                'contractor_price' => 85000,
                'reorder_level' => 20,
                'location' => 'Gudang D2',
                'barcode' => '8991102004002',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Avian Brands Tbk'
            ],
            
            // PIPA & FITTING
            [
                'name' => 'Pipa PVC Wavin 3 inch (75mm) 4 meter',
                'price' => 125000,
                'stock' => 120,
                'description' => 'Pipa PVC berkualitas tinggi untuk instalasi air bersih dan saluran pembuangan.',
                'slug' => 'pipa-pvc-wavin-3inch-4m',
                'unit' => 'batang',
                'min_order_qty' => 5,
                'wholesale_price' => 118000,
                'contractor_price' => 112000,
                'reorder_level' => 30,
                'location' => 'Yard E1',
                'barcode' => '8991102005001',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Wavin Duta Jaya Fiberglass'
            ],
            [
                'name' => 'Elbow PVC 3 inch 90 derajat',
                'price' => 25000,
                'stock' => 200,
                'description' => 'Elbow PVC 90° diameter 3 inch untuk sambungan pipa sudut siku-siku.',
                'slug' => 'elbow-pvc-3inch-90deg',
                'unit' => 'pcs',
                'min_order_qty' => 10,
                'wholesale_price' => 23000,
                'contractor_price' => 21000,
                'reorder_level' => 50,
                'location' => 'Gudang E2',
                'barcode' => '8991102005002',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Wavin Duta Jaya Fiberglass'
            ],
            
            // LISTRIK
            [
                'name' => 'Kabel NYA 2.5mm² 100 meter',
                'price' => 450000,
                'stock' => 50,
                'description' => 'Kabel instalasi rumah standar SNI untuk penerangan dan stop kontak.',
                'slug' => 'kabel-nya-2-5mm-100m',
                'unit' => 'roll',
                'min_order_qty' => 2,
                'wholesale_price' => 425000,
                'contractor_price' => 405000,
                'reorder_level' => 10,
                'location' => 'Gudang F1',
                'barcode' => '8991102006001',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Schneider Electric Indonesia'
            ],
            [
                'name' => 'MCB Schneider 10A 1 Phase',
                'price' => 75000,
                'stock' => 100,
                'description' => 'Circuit breaker 10 ampere 1 fasa untuk proteksi instalasi listrik rumah tangga.',
                'slug' => 'mcb-schneider-10a-1phase',
                'unit' => 'pcs',
                'min_order_qty' => 5,
                'wholesale_price' => 71000,
                'contractor_price' => 68000,
                'reorder_level' => 25,
                'location' => 'Gudang F2',
                'barcode' => '8991102006002',
                'is_bulk_only' => false,
                'supplier_name' => 'PT Schneider Electric Indonesia'
            ],
        ];
        
        // Create products with supplier assignment
        foreach ($buildingProducts as $productData) {
            // Find supplier by name
            $supplier = $suppliers->where('name', $productData['supplier_name'])->first();
            
            // Remove supplier_name from product data before creation
            $supplierName = $productData['supplier_name'];
            unset($productData['supplier_name']);
            
            // Add supplier_id
            $productData['supplier_id'] = $supplier ? $supplier->id : null;
            
            // Remove current_stock as it's not in the database schema
            
            $product = Product::create($productData);
            
            // Attach random categories (many-to-many relationship)
            if ($categories->count() > 0) {
                $product->categories()->attach($categories->random()->id);
            }
        }
        
        // Update existing fashion products with building store columns
        $this->updateExistingProducts();
    }
    
    /**
     * Update existing products with new building store columns
     */
    private function updateExistingProducts(): void
    {
        $existingProducts = Product::whereNull('unit')->get();
        $suppliers = Supplier::all();
        
        foreach ($existingProducts as $product) {
            $product->update([
                'unit' => 'pcs',
                'min_order_qty' => rand(1, 5),
                'wholesale_price' => round($product->price * 0.9),
                'contractor_price' => round($product->price * 0.85),
                'supplier_id' => $suppliers->random()->id ?? null,
                'reorder_level' => rand(10, 50),
                'location' => 'Gudang ' . chr(65 + rand(0, 5)) . rand(1, 10),
                'barcode' => '899110200' . str_pad($product->id, 4, '0', STR_PAD_LEFT),
                'is_bulk_only' => false,

            ]);
        }
    }
}
