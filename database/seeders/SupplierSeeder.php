<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT Semen Indonesia (Persero) Tbk',
                'company_name' => 'PT Semen Indonesia',
                'email' => 'sales@semenindonesia.co.id',
                'phone' => '021-7918234',
                'address' => 'Jl. Veteran No. 9, Jakarta Pusat',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '10110',
                'tax_number' => '01.001.844.9-091.000',
                'payment_terms' => 30,
                'is_active' => true,
                'notes' => 'Supplier utama semen Gresik, Tonasa, Padang. Minimum order 10 sak.'
            ],
            [
                'name' => 'PT Krakatau Steel (Persero) Tbk',
                'company_name' => 'Krakatau Steel',
                'email' => 'marketing@krakatausteel.com',
                'phone' => '0254-380222',
                'address' => 'Jl. Industry Raya, Cilegon',
                'city' => 'Cilegon',
                'province' => 'Banten',
                'postal_code' => '42435',
                'tax_number' => '01.001.694.7-431.000',
                'payment_terms' => 45,
                'is_active' => true,
                'notes' => 'Supplier besi beton, wiremesh, pelat. Credit terms negotiable untuk volume besar.'
            ],
            [
                'name' => 'PT Holcim Indonesia Tbk',
                'company_name' => 'Holcim Indonesia',
                'email' => 'customercare@holcim.com',
                'phone' => '021-7917108',
                'address' => 'Jl. TB Simatupang Kav 88, Jakarta Selatan',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12520',
                'tax_number' => '01.000.030.8-092.000',
                'payment_terms' => 30,
                'is_active' => true,
                'notes' => 'Supplier semen Holcim, ready mix concrete. Delivery schedule fixed per minggu.'
            ],
            [
                'name' => 'PT Mulia Industrindo Tbk',
                'company_name' => 'Mulia Ceramics',
                'email' => 'sales@mulia.co.id',
                'phone' => '021-4585588',
                'address' => 'Jl. Pulo Buaran Raya Blok AA-1, Jakarta Timur',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '13450',
                'tax_number' => '01.000.567.2-093.000',
                'payment_terms' => 21,
                'is_active' => true,
                'notes' => 'Supplier keramik Mulia brand. Minimum order per pallet (25 dus).'
            ],
            [
                'name' => 'CV Besi Jaya Mandiri',
                'company_name' => 'Besi Jaya',
                'email' => 'info@besijaya.com',
                'phone' => '021-8400789',
                'address' => 'Jl. Raya Bekasi Km 18, Cakung',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '13910',
                'tax_number' => '71.234.567.8-094.000',
                'payment_terms' => 14,
                'is_active' => true,
                'notes' => 'Distributor besi beton berbagai merk. Stock lengkap diameter 8-32mm.'
            ],
            [
                'name' => 'PT Avian Brands Tbk',
                'company_name' => 'Avian Brands',
                'email' => 'cs@avianbrands.com',
                'phone' => '021-4606868',
                'address' => 'Jl. Panjang Arteri Kelapa Dua, Tangerang',
                'city' => 'Tangerang',
                'province' => 'Banten',
                'postal_code' => '15810',
                'tax_number' => '01.003.298.7-411.000',
                'payment_terms' => 30,
                'is_active' => true,
                'notes' => 'Supplier cat Avitex, Avian, Glitex. Program trade in kaleng kosong.'
            ],
            [
                'name' => 'PT Wavin Duta Jaya Fiberglass',
                'company_name' => 'Wavin Indonesia',
                'email' => 'sales@wavin.co.id',
                'phone' => '021-8900456',
                'address' => 'Jl. Raya Serang Km 18.5, Cikupa',
                'city' => 'Tangerang',
                'province' => 'Banten',
                'postal_code' => '15710',
                'tax_number' => '01.004.123.9-411.000',
                'payment_terms' => 30,
                'is_active' => true,
                'notes' => 'Supplier pipa PVC, PPR, fitting. Garansi produk 20 tahun.'
            ],
            [
                'name' => 'PT Schneider Electric Indonesia',
                'company_name' => 'Schneider Electric',
                'email' => 'indonesia@schneider-electric.com',
                'phone' => '021-2953777',
                'address' => 'Jl. Casablanca Raya Kav 88, Jakarta Selatan',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12870',
                'tax_number' => '01.005.678.1-092.000',
                'payment_terms' => 45,
                'is_active' => true,
                'notes' => 'Supplier MCB, kontaktor, kabel NYA/NYM. Authorized dealer resmi.'
            ],
            [
                'name' => 'UD Kayu Jati Sejahtera',
                'company_name' => 'Kayu Jati Sejahtera',
                'email' => 'order@kayujati.com',
                'phone' => '0274-512345',
                'address' => 'Jl. Magelang Km 5, Yogyakarta',
                'city' => 'Yogyakarta',
                'province' => 'DIY Yogyakarta',
                'postal_code' => '55284',
                'tax_number' => '71.876.543.2-543.000',
                'payment_terms' => 7,
                'is_active' => true,
                'notes' => 'Supplier kayu jati, mahoni, meranti. Custom cutting available.'
            ],
            [
                'name' => 'PT Genteng Mantili',
                'company_name' => 'Mantili Ceramics',
                'email' => 'sales@mantili.co.id',
                'phone' => '0341-551234',
                'address' => 'Jl. Raya Malang-Surabaya Km 45, Malang',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65151',
                'tax_number' => '71.999.888.7-351.000',
                'payment_terms' => 14,
                'is_active' => true,
                'notes' => 'Supplier genteng keramik, metal, asbes. Minimum order 1000 pcs.'
            ]
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
