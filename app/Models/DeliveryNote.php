<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = [
        'delivery_number',
        'order_id',
        'customer_id',
        'delivery_date',
        'driver_name',
        'vehicle_number',
        'status',
        'recipient_name',
        'recipient_signature',
        'notes',
        'delivered_at'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETURNED = 'returned';

    /**
     * Relationship with Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relationship with DeliveryNoteItems
     */
    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    /**
     * Generate unique delivery number
     */
    public static function generateDeliveryNumber(): string
    {
        $prefix = 'SJ';
        $date = now()->format('ymd');
        $lastNumber = self::whereDate('created_at', now())
            ->where('delivery_number', 'like', "{$prefix}{$date}%")
            ->count();
        
        return $prefix . $date . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for in transit deliveries
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    /**
     * Scope for delivered
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Check if delivery is completed
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(string $recipientName = null, string $signature = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'recipient_name' => $recipientName ?: $this->recipient_name,
            'recipient_signature' => $signature ?: $this->recipient_signature,
        ]);
    }
}
