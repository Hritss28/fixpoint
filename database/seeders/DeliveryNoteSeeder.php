<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;

class DeliveryNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some orders to create delivery notes
        $orders = Order::where('status', 'completed')->limit(15)->get();
        
        foreach ($orders as $order) {
            if (rand(0, 1)) { // 50% chance this order has delivery note
                $deliveryNote = $this->createDeliveryNote($order);
                $this->createDeliveryNoteItems($deliveryNote, $order);
            }
        }
        
        // Create some additional test scenarios
        $this->createTestScenarios();
    }
    
    /**
     * Create a delivery note for an order
     */
    private function createDeliveryNote(Order $order): DeliveryNote
    {
        $deliveryDate = $order->created_at->addDays(rand(1, 3));
        $status = $this->getRandomStatus($deliveryDate);
        
        return DeliveryNote::create([
            'delivery_number' => $this->generateDeliveryNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->user_id,
            'delivery_date' => $deliveryDate,
            'driver_name' => $this->getRandomDriverName(),
            'vehicle_number' => $this->generateVehicleNumber(),
            'status' => $status,
            'recipient_name' => $this->getRecipientName($order, $status),
            'recipient_signature' => $status === 'delivered' ? $this->generateFakeSignature() : null,
            'notes' => $this->generateDeliveryNotes($status),
            'delivered_at' => $status === 'delivered' ? $deliveryDate->addHours(rand(2, 8)) : null,
            'created_at' => $order->created_at,
            'updated_at' => $status === 'delivered' ? $deliveryDate->addHours(rand(2, 8)) : $order->created_at
        ]);
    }
    
    /**
     * Create delivery note items
     */
    private function createDeliveryNoteItems(DeliveryNote $deliveryNote, Order $order): void
    {
        // Get some random products for this delivery
        $products = Product::inRandomOrder()->limit(rand(2, 5))->get();
        
        foreach ($products as $product) {
            DeliveryNoteItem::create([
                'delivery_note_id' => $deliveryNote->id,
                'product_id' => $product->id,
                'quantity_ordered' => rand(1, 20),
                'quantity_delivered' => rand(1, 20),
                'unit' => $product->unit ?? 'pcs',
                'notes' => rand(0, 3) === 0 ? $this->getRandomItemNotes() : null
            ]);
        }
    }
    
    /**
     * Generate delivery number
     */
    private function generateDeliveryNumber(): string
    {
        $prefix = 'DN';
        $year = date('Y');
        $month = date('m');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$year}{$month}{$sequence}";
    }
    
    /**
     * Generate random vehicle number
     */
    private function generateVehicleNumber(): string
    {
        $prefixes = ['B', 'D', 'F', 'AA', 'AB'];
        $prefix = $prefixes[array_rand($prefixes)];
        $numbers = rand(1000, 9999);
        $suffixes = ['ABC', 'DEF', 'GHI', 'JKL', 'MNO'];
        $suffix = $suffixes[array_rand($suffixes)];
        
        return "{$prefix} {$numbers} {$suffix}";
    }
    
    /**
     * Get random driver name
     */
    private function getRandomDriverName(): string
    {
        $drivers = [
            'Budi Santoso',
            'Agus Priyanto', 
            'Slamet Riyadi',
            'Joko Susilo',
            'Andi Wijaya',
            'Dedi Kurniawan',
            'Heri Setiawan',
            'Wawan Gunawan',
            'Eko Prasetyo',
            'Bambang Sutrisno'
        ];
        
        return $drivers[array_rand($drivers)];
    }
    
    /**
     * Get delivery status based on date
     */
    private function getRandomStatus(Carbon $deliveryDate): string
    {
        $daysDiff = Carbon::now()->diffInDays($deliveryDate);
        
        if ($daysDiff > 5) {
            return 'delivered'; // Old deliveries are mostly delivered
        } elseif ($daysDiff > 2) {
            return rand(0, 1) ? 'delivered' : 'in_transit';
        } else {
            $statuses = ['pending', 'in_transit', 'delivered'];
            return $statuses[array_rand($statuses)];
        }
    }
    
    /**
     * Get recipient name based on order and status
     */
    private function getRecipientName(Order $order, string $status): ?string
    {
        if ($status !== 'delivered') {
            return null;
        }
        
        $recipients = [
            $order->name, // Customer themselves
            'Kepala Gudang',
            'Site Manager', 
            'Mandor',
            'Security',
            'Admin Proyek'
        ];
        
        return $recipients[array_rand($recipients)];
    }
    
    /**
     * Generate fake signature (base64 placeholder)
     */
    private function generateFakeSignature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }
    
    /**
     * Generate delivery notes based on status
     */
    private function generateDeliveryNotes(string $status): string
    {
        return match($status) {
            'pending' => 'Menunggu jadwal pengiriman. Koordinasi dengan customer untuk waktu terima.',
            'in_transit' => 'Barang dalam perjalanan. Driver sudah menghubungi customer.',
            'delivered' => 'Barang sudah diterima dengan baik. Tidak ada komplain.',
            'returned' => 'Barang dikembalikan karena customer tidak ada di tempat.',
            default => 'Status pengiriman normal.'
        };
    }
    
    /**
     * Get random item notes
     */
    private function getRandomItemNotes(): string
    {
        $notes = [
            'Barang sesuai pesanan',
            'Ada sedikit lecet pada kemasan',
            'Customer request double check kualitas',
            'Expired date masih lama',
            'Kemasan rusak diganti baru'
        ];
        
        return $notes[array_rand($notes)];
    }
    
    /**
     * Create test scenarios for different delivery statuses
     */
    private function createTestScenarios(): void
    {
        $orders = Order::limit(5)->get();
        
        if ($orders->count() > 0) {
            // Scenario 1: Delivery in transit
            DeliveryNote::create([
                'delivery_number' => 'DN202411190001',
                'order_id' => $orders[0]->id,
                'customer_id' => $orders[0]->user_id,
                'delivery_date' => Carbon::now(),
                'driver_name' => 'Budi Santoso',
                'vehicle_number' => 'B 1234 ABC',
                'status' => 'in_transit',
                'recipient_name' => null,
                'recipient_signature' => null,
                'notes' => 'Sedang dalam perjalanan. Estimasi tiba 14:00 WIB.',
                'delivered_at' => null
            ]);
            
            // Scenario 2: Delivered today
            if ($orders->count() > 1) {
                DeliveryNote::create([
                    'delivery_number' => 'DN202411190002',
                    'order_id' => $orders[1]->id,
                    'customer_id' => $orders[1]->user_id,
                    'delivery_date' => Carbon::now(),
                    'driver_name' => 'Agus Priyanto',
                    'vehicle_number' => 'B 5678 DEF',
                    'status' => 'delivered',
                    'recipient_name' => 'Kepala Gudang',
                    'recipient_signature' => $this->generateFakeSignature(),
                    'notes' => 'Barang sudah diterima dengan baik. Tidak ada komplain.',
                    'delivered_at' => Carbon::now()->subHours(2)
                ]);
            }
            
            // Scenario 3: Pending delivery (scheduled for tomorrow)
            if ($orders->count() > 2) {
                DeliveryNote::create([
                    'delivery_number' => 'DN202411200001',
                    'order_id' => $orders[2]->id,
                    'customer_id' => $orders[2]->user_id,
                    'delivery_date' => Carbon::tomorrow(),
                    'driver_name' => 'Slamet Riyadi',
                    'vehicle_number' => 'B 9999 GHI',
                    'status' => 'pending',
                    'recipient_name' => null,
                    'recipient_signature' => null,
                    'notes' => 'Dijadwalkan pengiriman besok pagi jam 08:00 WIB.',
                    'delivered_at' => null
                ]);
            }
        }
    }
}
