<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Semen Gresik'],
            ['name' => 'Semen Tiga Roda'],
            ['name' => 'Holcim'],
            ['name' => 'Nippon Paint'],
            ['name' => 'Dulux'],
            ['name' => 'Avian'],
            ['name' => 'Mowilex'],
            ['name' => 'Toto'],
            ['name' => 'American Standard'],
            ['name' => 'Rucika'],
            ['name' => 'Wavin'],
            ['name' => 'Pipa Pralon'],
            ['name' => 'Schneider Electric'],
            ['name' => 'Panasonic'],
            ['name' => 'Philips'],
            ['name' => 'Bosch'],
            ['name' => 'Makita'],
            ['name' => 'Stanley'],
            ['name' => 'Roman Ceramics'],
            ['name' => 'Granito'],
            ['name' => 'Mulia Ceramics'],
            ['name' => 'Jayamix'],
            ['name' => 'Bata Ringan Hebel'],
            ['name' => 'Krakatau Steel'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate($brand);
        }
    }
}