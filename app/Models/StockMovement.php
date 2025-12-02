<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
        'previous_stock',
        'new_stock',
        'is_reserved',
        'adjustment_type'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'is_reserved' => 'boolean',
    ];

    /**
     * Movement types
     */
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RESERVED = 'reserved';
    
    /**
     * Get allowed movement types
     */
    public static function getAllowedTypes(): array
    {
        return [
            self::TYPE_IN,
            self::TYPE_OUT,
            self::TYPE_ADJUSTMENT,
            self::TYPE_RESERVED,
        ];
    }
    
    /**
     * Boot method to add validation
     */
    protected static function booted()
    {
        static::creating(function ($stockMovement) {
            if (!in_array($stockMovement->type, self::getAllowedTypes())) {
                throw new \InvalidArgumentException("Invalid stock movement type: {$stockMovement->type}");
            }
        });
        
        static::updating(function ($stockMovement) {
            if (!in_array($stockMovement->type, self::getAllowedTypes())) {
                throw new \InvalidArgumentException("Invalid stock movement type: {$stockMovement->type}");
            }
        });
    }

    /**
     * Relationship with Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reference model (polymorphic)
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $model = match ($this->reference_type) {
                'order' => Order::class,
                'purchase' => 'App\Models\Purchase', // Future model
                default => null,
            };
            
            if ($model) {
                return $model::find($this->reference_id);
            }
        }
        
        return null;
    }

    /**
     * Scope for stock in movements
     */
    public function scopeStockIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    /**
     * Scope for stock out movements
     */
    public function scopeStockOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    /**
     * Scope for adjustments
     */
    public function scopeAdjustments($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    /**
     * Scope for product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Create stock movement and update product stock
     */
    public static function createMovement(array $data): self
    {
        $movement = self::create($data);
        
        // Update product stock
        $product = Product::find($data['product_id']);
        if ($product) {
            $adjustment = match ($data['type']) {
                self::TYPE_IN, self::TYPE_ADJUSTMENT => $data['quantity'],
                self::TYPE_OUT => -$data['quantity'],
                default => 0,
            };
            
            $product->increment('stock', $adjustment);
        }
        
        return $movement;
    }
}
