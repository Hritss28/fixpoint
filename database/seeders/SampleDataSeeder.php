<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Review;
use App\Models\ProductReview;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\PromoCode;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\StockMovement;
use App\Models\Report;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding sample data...');

        // 1. Create sample customers
        $this->command->info('Creating customers...');
        $customers = [];
        $customerData = [
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '081234567890', 'customer_type' => 'retail'],
            ['name' => 'PT Konstruksi Jaya', 'email' => 'kontraktor@example.com', 'phone' => '081234567891', 'customer_type' => 'contractor', 'company_name' => 'PT Konstruksi Jaya'],
            ['name' => 'Toko Bangunan Makmur', 'email' => 'wholesale@example.com', 'phone' => '081234567892', 'customer_type' => 'wholesale', 'company_name' => 'TB Makmur'],
            ['name' => 'CV Distributor Utama', 'email' => 'distributor@example.com', 'phone' => '081234567893', 'customer_type' => 'distributor', 'company_name' => 'CV Distributor Utama'],
            ['name' => 'Ibu Siti Rahayu', 'email' => 'siti@example.com', 'phone' => '081234567894', 'customer_type' => 'retail'],
            ['name' => 'Pak Ahmad', 'email' => 'ahmad@example.com', 'phone' => '081234567895', 'customer_type' => 'retail'],
            ['name' => 'PT Bangun Sejahtera', 'email' => 'bangun@example.com', 'phone' => '081234567896', 'customer_type' => 'contractor', 'company_name' => 'PT Bangun Sejahtera'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@example.com', 'phone' => '081234567897', 'customer_type' => 'retail'],
        ];

        foreach ($customerData as $data) {
            $customers[] = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ])
            );
        }

        // 2. Create suppliers
        $this->command->info('Creating suppliers...');
        $supplierData = [
            ['name' => 'PT Semen Indonesia', 'email' => 'semen@supplier.com', 'phone' => '021-1234567', 'address' => 'Jakarta', 'city' => 'Jakarta', 'is_active' => true],
            ['name' => 'CV Baja Ringan Nusantara', 'email' => 'baja@supplier.com', 'phone' => '021-2345678', 'address' => 'Surabaya', 'city' => 'Surabaya', 'is_active' => true],
            ['name' => 'Toko Cat Cemerlang', 'email' => 'cat@supplier.com', 'phone' => '021-3456789', 'address' => 'Bandung', 'city' => 'Bandung', 'is_active' => true],
            ['name' => 'PT Keramik Indah', 'email' => 'keramik@supplier.com', 'phone' => '021-4567890', 'address' => 'Semarang', 'city' => 'Semarang', 'is_active' => true],
            ['name' => 'CV Alat Teknik Maju', 'email' => 'alat@supplier.com', 'phone' => '021-5678901', 'address' => 'Yogyakarta', 'city' => 'Yogyakarta', 'is_active' => true],
        ];

        foreach ($supplierData as $data) {
            Supplier::firstOrCreate(['email' => $data['email']], $data);
        }

        // 3. Create promo codes
        $this->command->info('Creating promo codes...');
        $promoData = [
            ['code' => 'WELCOME15', 'promotion_title' => 'Diskon Pelanggan Baru', 'description' => 'Diskon 15% untuk pelanggan baru', 'discount_type' => 'percentage', 'discount' => 15, 'discount_value' => 15, 'minimum_order' => 100000, 'maximum_discount' => 150000, 'is_active' => true, 'show_on_homepage' => true],
            ['code' => 'GROSIR10', 'promotion_title' => 'Diskon Grosir', 'description' => 'Diskon 10% untuk pembelian grosir', 'discount_type' => 'percentage', 'discount' => 10, 'discount_value' => 10, 'minimum_order' => 500000, 'maximum_discount' => 500000, 'is_active' => true, 'show_on_homepage' => true],
            ['code' => 'HEMAT50K', 'promotion_title' => 'Potongan Langsung 50rb', 'description' => 'Potongan langsung Rp50.000', 'discount_type' => 'fixed', 'discount' => 50000, 'discount_value' => 50000, 'minimum_order' => 300000, 'is_active' => true, 'show_on_homepage' => false],
            ['code' => 'KONTRAKTOR20', 'promotion_title' => 'Diskon Kontraktor', 'description' => 'Diskon khusus kontraktor 20%', 'discount_type' => 'percentage', 'discount' => 20, 'discount_value' => 20, 'minimum_order' => 1000000, 'maximum_discount' => 1000000, 'is_active' => true, 'show_on_homepage' => true],
        ];

        foreach ($promoData as $data) {
            PromoCode::firstOrCreate(['code' => $data['code']], array_merge($data, [
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'usage_limit' => rand(50, 200),
            ]));
        }

        // 4. Get products for orders and reviews
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping orders and reviews.');
            return;
        }

        // 5. Create orders with items
        $this->command->info('Creating orders...');
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed'];
        $paymentStatuses = ['pending', 'partial', 'paid'];

        foreach ($customers as $customer) {
            $orderCount = rand(1, 5);
            for ($i = 0; $i < $orderCount; $i++) {
                $status = $statuses[array_rand($statuses)];
                $paymentStatus = $paymentStatuses[array_rand($paymentStatuses)];

                // Calculate items first
                $itemCount = rand(1, 4);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $subtotal = 0;
                $orderItems = [];

                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 10);
                    $price = $product->price;
                    $itemSubtotal = $quantity * $price;
                    $subtotal += $itemSubtotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'price' => $price,
                        'quantity' => $quantity,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $shippingCost = rand(0, 5) * 10000;
                $taxAmount = $subtotal * 0.11;
                $totalAmount = $subtotal + $shippingCost + $taxAmount;

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $customer->id,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'shipping_address' => 'Jl. Contoh No. ' . rand(1, 100) . ', Jakarta',
                    'shipping_phone' => '08' . rand(1000000000, 9999999999),
                    'shipping_postal_code' => (string) rand(10000, 99999),
                    'shipping_cost' => $shippingCost,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'notes' => 'Pesanan sample #' . ($i + 1),
                ]);

                // Create order items
                foreach ($orderItems as $itemData) {
                    OrderItem::create(array_merge($itemData, ['order_id' => $order->id]));
                }
            }
        }

        // 6. Create product reviews
        $this->command->info('Creating product reviews...');
        $reviewTexts = [
            5 => [
                'Produk sangat bagus, kualitas terjamin!',
                'Pengiriman cepat, barang sesuai deskripsi.',
                'Harga terjangkau dengan kualitas premium.',
                'Sangat puas dengan pembelian ini!',
                'Recommended seller, pasti beli lagi.',
            ],
            4 => [
                'Produk bagus, sesuai ekspektasi.',
                'Kualitas oke, pengiriman tepat waktu.',
                'Barang bagus, kemasan rapi.',
                'Lumayan puas, akan order lagi.',
            ],
            3 => [
                'Produk standar, sesuai harga.',
                'Cukup puas dengan produknya.',
                'Barang sampai dengan kondisi baik.',
            ],
            2 => [
                'Kualitas kurang sesuai ekspektasi.',
                'Pengiriman agak lama.',
            ],
            1 => [
                'Produk tidak sesuai foto.',
            ],
        ];

        foreach ($products as $product) {
            $reviewCount = rand(2, 8);
            $reviewers = collect($customers)->random(min($reviewCount, count($customers)));

            foreach ($reviewers as $reviewer) {
                $rating = $this->weightedRating();
                $texts = $reviewTexts[$rating];
                $reviewText = $texts[array_rand($texts)];

                Review::firstOrCreate(
                    ['user_id' => $reviewer->id, 'product_id' => $product->id],
                    [
                        'rating' => $rating,
                        'comment' => $reviewText,
                        'is_approved' => true,
                        'created_at' => now()->subDays(rand(1, 60)),
                    ]
                );
            }
        }

        // 8. Create newsletter subscribers
        $this->command->info('Creating newsletter subscribers...');
        $emails = ['subscriber1@mail.com', 'subscriber2@mail.com', 'subscriber3@mail.com', 'subscriber4@mail.com', 'subscriber5@mail.com'];
        foreach ($emails as $email) {
            NewsletterSubscriber::firstOrCreate(
                ['email' => $email],
                [
                    'status' => 'active',
                ]
            );
        }

        // 9. Create contact messages
        $this->command->info('Creating contact messages...');
        $messages = [
            ['name' => 'Agus', 'email' => 'agus@mail.com', 'subject' => 'Pertanyaan Harga', 'message' => 'Apakah ada diskon untuk pembelian dalam jumlah besar?', 'is_read' => true],
            ['name' => 'Rina', 'email' => 'rina@mail.com', 'subject' => 'Kerja Sama', 'message' => 'Saya tertarik untuk menjadi distributor di daerah Surabaya.', 'is_read' => true],
            ['name' => 'Joko', 'email' => 'joko@mail.com', 'subject' => 'Komplain Pengiriman', 'message' => 'Barang yang saya terima ada yang rusak.', 'is_read' => false],
        ];
        foreach ($messages as $msg) {
            ContactMessage::firstOrCreate(['email' => $msg['email'], 'subject' => $msg['subject']], $msg);
        }

        // 10. Create reports
        $this->command->info('Creating sample reports...');
        $reports = [
            ['name' => 'Laporan Penjualan Bulanan', 'type' => 'sales', 'format' => 'pdf', 'date_range_type' => 'this_month'],
            ['name' => 'Laporan Stok Produk', 'type' => 'inventory', 'format' => 'excel', 'date_range_type' => 'this_week'],
            ['name' => 'Laporan Piutang Pelanggan', 'type' => 'receivables', 'format' => 'pdf', 'date_range_type' => 'this_month'],
        ];
        foreach ($reports as $report) {
            Report::firstOrCreate(['name' => $report['name']], array_merge($report, [
                'is_scheduled' => false,
                'include_charts' => true,
                'show_totals' => true,
            ]));
        }

        $this->command->info('Sample data seeding completed!');
    }

    private function weightedRating(): int
    {
        $weights = [5 => 40, 4 => 35, 3 => 15, 2 => 7, 1 => 3];
        $rand = rand(1, 100);
        $cumulative = 0;
        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $rating;
            }
        }
        return 5;
    }
}
