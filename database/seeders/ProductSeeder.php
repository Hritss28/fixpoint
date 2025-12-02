<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil kategori yang sudah dibuat
        $categories = \App\Models\Category::pluck('id', 'name');

        $products = [
            // Semen & Mortar
            [
                'name' => 'Semen Portland Gresik 50kg',
                'price' => 72000,
                'stock' => 500,
                'description' => 'Semen Portland Type I kualitas premium untuk konstruksi umum dengan daya ikat yang kuat dan tahan lama.',
                'slug' => 'semen-portland-gresik-50kg',
                'category_id' => $categories['Semen & Mortar'] ?? 1,
                'unit' => 'sak',
                'min_order_qty' => 1,
                'wholesale_price' => 68000,
                'contractor_price' => 65000,
            ],
            [
                'name' => 'Semen Tiga Roda 40kg',
                'price' => 58000,
                'stock' => 300,
                'description' => 'Semen berkualitas tinggi untuk berbagai keperluan konstruksi dengan kemasan 40kg yang praktis.',
                'slug' => 'semen-tiga-roda-40kg',
                'category_id' => $categories['Semen & Mortar'] ?? 1,
                'unit' => 'sak',
                'min_order_qty' => 1,
                'wholesale_price' => 55000,
                'contractor_price' => 52000,
            ],
            [
                'name' => 'Mortar Instan MU-400 25kg',
                'price' => 45000,
                'stock' => 200,
                'description' => 'Mortar siap pakai untuk pemasangan keramik, batu alam, dan material dinding.',
                'slug' => 'mortar-instan-mu400-25kg',
                'category_id' => $categories['Semen & Mortar'] ?? 1,
                'unit' => 'sak',
                'min_order_qty' => 5,
                'wholesale_price' => 42000,
                'contractor_price' => 40000,
            ],

            // Besi & Baja
            [
                'name' => 'Besi Beton Ulir 12mm - 12m',
                'price' => 85000,
                'stock' => 150,
                'description' => 'Besi beton ulir diameter 12mm panjang 12 meter untuk struktur bangunan dengan SNI terjamin.',
                'slug' => 'besi-beton-ulir-12mm-12m',
                'category_id' => $categories['Besi & Baja'] ?? 2,
                'unit' => 'batang',
                'min_order_qty' => 10,
                'wholesale_price' => 80000,
                'contractor_price' => 76000,
            ],
            [
                'name' => 'Besi Hollow 4x4 Tebal 1mm',
                'price' => 32000,
                'stock' => 120,
                'description' => 'Besi hollow kotak 4x4 cm dengan ketebalan 1mm untuk rangka kanopi, pagar, dan konstruksi ringan.',
                'slug' => 'besi-hollow-4x4-tebal-1mm',
                'category_id' => $categories['Besi & Baja'] ?? 2,
                'unit' => 'batang',
                'min_order_qty' => 20,
                'wholesale_price' => 30000,
                'contractor_price' => 28000,
            ],

            // Kayu & Multiplek
            [
                'name' => 'Kayu Meranti Merah 5x10x400cm',
                'price' => 125000,
                'stock' => 80,
                'description' => 'Kayu meranti merah kering oven ukuran 5x10x400cm untuk konstruksi dan furniture.',
                'slug' => 'kayu-meranti-merah-5x10x400cm',
                'category_id' => $categories['Kayu & Multiplek'] ?? 3,
                'unit' => 'batang',
                'min_order_qty' => 5,
                'wholesale_price' => 118000,
                'contractor_price' => 112000,
            ],
            [
                'name' => 'Multiplek 18mm 122x244cm',
                'price' => 285000,
                'stock' => 60,
                'description' => 'Multiplek kualitas export grade AA tebal 18mm untuk furniture dan interior.',
                'slug' => 'multiplek-18mm-122x244cm',
                'category_id' => $categories['Kayu & Multiplek'] ?? 3,
                'unit' => 'lembar',
                'min_order_qty' => 3,
                'wholesale_price' => 275000,
                'contractor_price' => 265000,
            ],

            // Cat & Finishing
            [
                'name' => 'Cat Tembok Nippon Paint Vinilex 25kg',
                'price' => 485000,
                'stock' => 45,
                'description' => 'Cat tembok interior berkualitas premium dengan daya tutup tinggi dan warna tahan lama.',
                'slug' => 'cat-tembok-nippon-paint-vinilex-25kg',
                'category_id' => $categories['Cat & Finishing'] ?? 4,
                'unit' => 'pail',
                'min_order_qty' => 2,
                'wholesale_price' => 465000,
                'contractor_price' => 445000,
            ],
            [
                'name' => 'Cat Besi Avian Anti Karat 1kg',
                'price' => 68000,
                'stock' => 75,
                'description' => 'Cat besi anti karat dengan daya tahan tinggi terhadap cuaca dan korosi.',
                'slug' => 'cat-besi-avian-anti-karat-1kg',
                'category_id' => $categories['Cat & Finishing'] ?? 4,
                'unit' => 'kaleng',
                'min_order_qty' => 6,
                'wholesale_price' => 64000,
                'contractor_price' => 61000,
            ],

            // Keramik & Granit
            [
                'name' => 'Keramik Lantai Roman dBeton 60x60cm',
                'price' => 89000,
                'stock' => 200,
                'description' => 'Keramik lantai motif beton modern dengan permukaan matt anti slip.',
                'slug' => 'keramik-lantai-roman-dbeton-60x60cm',
                'category_id' => $categories['Keramik & Granit'] ?? 5,
                'unit' => 'm2',
                'min_order_qty' => 10,
                'wholesale_price' => 84000,
                'contractor_price' => 79000,
            ],
            [
                'name' => 'Granit Alam Grey Absolute 60x60cm',
                'price' => 165000,
                'stock' => 85,
                'description' => 'Granit alam premium dengan motif abu-abu elegan untuk lantai dan dinding.',
                'slug' => 'granit-alam-grey-absolute-60x60cm',
                'category_id' => $categories['Keramik & Granit'] ?? 5,
                'unit' => 'm2',
                'min_order_qty' => 5,
                'wholesale_price' => 155000,
                'contractor_price' => 148000,
            ],

            // Pipa & Fitting
            [
                'name' => 'Pipa PVC Rucika 4 inch AW',
                'price' => 95000,
                'stock' => 100,
                'description' => 'Pipa PVC diameter 4 inch kelas AW untuk instalasi air bersih dengan standar SNI.',
                'slug' => 'pipa-pvc-rucika-4-inch-aw',
                'category_id' => $categories['Pipa & Fitting'] ?? 6,
                'unit' => 'batang',
                'min_order_qty' => 10,
                'wholesale_price' => 89000,
                'contractor_price' => 85000,
            ],
            [
                'name' => 'Elbow PVC 4 inch 90 derajat',
                'price' => 18500,
                'stock' => 150,
                'description' => 'Fitting elbow 90 derajat diameter 4 inch untuk sambungan pipa air.',
                'slug' => 'elbow-pvc-4-inch-90-derajat',
                'category_id' => $categories['Pipa & Fitting'] ?? 6,
                'unit' => 'pcs',
                'min_order_qty' => 20,
                'wholesale_price' => 17000,
                'contractor_price' => 16000,
            ],

            // Listrik & Kabel
            [
                'name' => 'Kabel NYM 3x2.5mm Eterna 100m',
                'price' => 485000,
                'stock' => 35,
                'description' => 'Kabel listrik NYM 3 core x 2.5mm² untuk instalasi rumah tangga dengan standar SNI.',
                'slug' => 'kabel-nym-3x25mm-eterna-100m',
                'category_id' => $categories['Listrik & Kabel'] ?? 7,
                'unit' => 'roll',
                'min_order_qty' => 1,
                'wholesale_price' => 465000,
                'contractor_price' => 445000,
            ],
            [
                'name' => 'MCB 1 Phase 16A Schneider',
                'price' => 85000,
                'stock' => 80,
                'description' => 'Miniature Circuit Breaker 1 phase 16 ampere merk Schneider untuk proteksi listrik.',
                'slug' => 'mcb-1-phase-16a-schneider',
                'category_id' => $categories['Listrik & Kabel'] ?? 7,
                'unit' => 'pcs',
                'min_order_qty' => 5,
                'wholesale_price' => 80000,
                'contractor_price' => 76000,
            ],

            // Alat & Perkakas
            [
                'name' => 'Mesin Bor Bosch GSB 550 RE',
                'price' => 675000,
                'stock' => 25,
                'description' => 'Mesin bor listrik dengan fungsi palu untuk beton, kayu, dan metal.',
                'slug' => 'mesin-bor-bosch-gsb-550-re',
                'category_id' => $categories['Alat & Perkakas'] ?? 11,
                'unit' => 'unit',
                'min_order_qty' => 1,
                'wholesale_price' => 645000,
                'contractor_price' => 615000,
            ],
            [
                'name' => 'Palu Besi 1kg Stanley',
                'price' => 125000,
                'stock' => 40,
                'description' => 'Palu besi dengan gagang fiber berkualitas tinggi untuk pekerjaan konstruksi.',
                'slug' => 'palu-besi-1kg-stanley',
                'category_id' => $categories['Alat & Perkakas'] ?? 11,
                'unit' => 'pcs',
                'min_order_qty' => 3,
                'wholesale_price' => 118000,
                'contractor_price' => 112000,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}