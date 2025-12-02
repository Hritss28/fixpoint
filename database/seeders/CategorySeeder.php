<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Semen & Mortar',
                'description' => 'Semen Portland, mortar, compound, dan bahan pengikat lainnya',
            ],
            [
                'name' => 'Besi & Baja',
                'description' => 'Besi beton, besi hollow, plat besi, dan material baja konstruksi',
            ],
            [
                'name' => 'Kayu & Multiplek',
                'description' => 'Kayu meranti, multiplek, triplek, dan bahan kayu konstruksi',
            ],
            [
                'name' => 'Cat & Finishing',
                'description' => 'Cat tembok, cat besi, primer, politur, dan bahan finishing',
            ],
            [
                'name' => 'Keramik & Granit',
                'description' => 'Keramik lantai, keramik dinding, granit, dan material penutup lantai',
            ],
            [
                'name' => 'Pipa & Fitting',
                'description' => 'Pipa PVC, pipa galvanis, fitting, valve, dan aksesoris plumbing',
            ],
            [
                'name' => 'Listrik & Kabel',
                'description' => 'Kabel listrik, stop kontak, saklar, MCB, dan peralatan elektrikal',
            ],
            [
                'name' => 'Genteng & Atap',
                'description' => 'Genteng tanah liat, genteng metal, seng gelombang, dan bahan atap',
            ],
            [
                'name' => 'Pintu & Jendela',
                'description' => 'Pintu kayu, pintu aluminium, jendela, dan aksesoris pintu-jendela',
            ],
            [
                'name' => 'Bata & Batako',
                'description' => 'Bata merah, batako press, hebel, dan material dinding',
            ],
            [
                'name' => 'Alat & Perkakas',
                'description' => 'Alat tukang, mesin konstruksi, perkakas, dan peralatan kerja',
            ],
            [
                'name' => 'Sanitari & Bathroom',
                'description' => 'Closet, wastafel, shower, dan perlengkapan kamar mandi',
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'] ?? null,
            ]);
        }
    }
}