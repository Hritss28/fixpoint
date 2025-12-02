<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'shipping_address',
        'shipping_postal_code',
        'shipping_phone',
        'notes',
        'payment_method',
        'payment_status',
        'transaction_id',
        'snap_token',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'shipping_method',
        'payment_token',
        'payment_details',
        'promo_code_id',
        'cancelled_at',
        // Building store fields
        'customer_type',
        'payment_term_days',
        'due_date',
        'delivery_note_id',
        'project_name',
        'tax_invoice_number',
        'payment_status_type',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        // Building store casts
        'payment_term_days' => 'integer',
        'due_date' => 'date',
    ];

    protected function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;
        
        if ($value === 'cancelled' && empty($this->attributes['cancelled_at'])) {
            $this->attributes['cancelled_at'] = now();
        }
    }

    protected function setPaymentStatusAttribute($value)
    {
        $this->attributes['payment_status'] = $value;
        
        if ($value === 'cancelled' && empty($this->attributes['cancelled_at'])) {
            $this->attributes['cancelled_at'] = now();
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pending</span>',
            'processing' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Processing</span>',
            'completed' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Completed</span>',
            'cancelled' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Cancelled</span>',
        ];

        return $statuses[$this->status] ?? $statuses['pending'];
    }

    public function getPaymentStatusLabelAttribute()
    {
        $statuses = [
            'pending' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pending</span>',
            'paid' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Paid</span>',
            'failed' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Failed</span>',
            'cancelled' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Failed</span>',
            'expired' => '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Expired</span>',
        ];

        return $statuses[$this->payment_status] ?? $statuses['pending'];
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the address associated with the order.
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    // Sesuaikan method untuk menghitung item yang sudah direview
    public function getReviewedItemsCountAttribute()
    {
        // Ambil produk ID yang sudah direview di order ini
        $reviewedProductIds = DB::table('product_reviews')
            ->where('order_id', $this->id)
            ->pluck('product_id')
            ->toArray();
        
        // Hitung berapa item order yang produknya sudah direview
        return DB::table('order_items')
            ->where('order_id', $this->id)
            ->whereIn('product_id', $reviewedProductIds)
            ->count();
    }

    // Method untuk menghitung item yang belum direview
    public function getUnreviewedItemsCountAttribute()
    {
        $totalItems = $this->items()->count();
        $reviewedItems = $this->getReviewedItemsCountAttribute();
        
        return $totalItems - $reviewedItems;
    }

    // ===== BUILDING STORE ENHANCEMENTS =====

    /**
     * Relationship with Delivery Note
     */
    public function deliveryNote()
    {
        return $this->hasOne(DeliveryNote::class);
    }

    /**
     * Relationship with Payment Term
     */
    public function paymentTerm()
    {
        return $this->hasOne(PaymentTerm::class);
    }

    /**
     * Check if order uses credit/tempo payment
     */
    public function isCreditPayment(): bool
    {
        return $this->payment_term_days > 0;
    }

    /**
     * Check if order is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid']) || 
               in_array($this->payment_status_type, [PaymentTerm::STATUS_PAID]);
    }

    /**
     * Get customer type label
     */
    public function getCustomerTypeLabelAttribute(): string
    {
        return match($this->customer_type) {
            'retail' => 'Retail',
            'wholesale' => 'Grosir',
            'contractor' => 'Kontraktor',
            'distributor' => 'Distributor',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        return $this->due_date ? $this->due_date->format('d M Y') : null;
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        return $this->due_date ? now()->diffInDays($this->due_date, false) : null;
    }

    /**
     * Generate delivery note for this order
     */
    public function createDeliveryNote(array $data = []): DeliveryNote
    {
        $deliveryNote = DeliveryNote::create([
            'delivery_number' => DeliveryNote::generateDeliveryNumber(),
            'order_id' => $this->id,
            'customer_id' => $this->user_id,
            'delivery_date' => $data['delivery_date'] ?? now()->addDay(),
            'driver_name' => $data['driver_name'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'status' => DeliveryNote::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        // Create delivery note items from order items
        foreach ($this->items as $item) {
            DeliveryNoteItem::create([
                'delivery_note_id' => $deliveryNote->id,
                'product_id' => $item->product_id,
                'product_name' => $item->name,
                'sku' => $item->product->sku ?? null,
                'quantity' => $item->quantity,
                'unit' => $item->product->unit ?? 'pcs',
                'price' => $item->price,
            ]);
        }

        // Update order with delivery note reference
        $this->update(['delivery_note_id' => $deliveryNote->id]);

        return $deliveryNote;
    }

    /**
     * Create payment term for this order
     */
    public function createPaymentTerm(): ?PaymentTerm
    {
        if (!$this->isCreditPayment()) {
            return null;
        }

        return PaymentTerm::create([
            'order_id' => $this->id,
            'customer_id' => $this->user_id,
            'due_date' => $this->due_date,
            'amount' => $this->total_amount,
            'paid_amount' => 0,
            'status' => PaymentTerm::STATUS_PENDING,
        ]);
    }

    /**
     * Scope for credit orders
     */
    public function scopeCreditOrders($query)
    {
        return $query->where('payment_term_days', '>', 0);
    }

    /**
     * Scope for cash orders
     */
    public function scopeCashOrders($query)
    {
        return $query->where('payment_term_days', '=', 0);
    }

    /**
     * Scope for overdue orders
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('payment_status', ['paid'])
            ->whereNotIn('payment_status_type', [PaymentTerm::STATUS_PAID]);
    }

    /**
     * Scope by customer type
     */
    public function scopeByCustomerType($query, string $customerType)
    {
        return $query->where('customer_type', $customerType);
    }

    /**
     * Generate order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('ymd');
        $lastNumber = self::whereDate('created_at', now())
            ->where('order_number', 'like', "{$prefix}{$date}%")
            ->count();
        
        return $prefix . $date . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and set due date based on payment terms
     */
    public function calculateDueDate(): void
    {
        if ($this->payment_term_days > 0) {
            $this->update([
                'due_date' => now()->addDays($this->payment_term_days)
            ]);
        }
    }
}