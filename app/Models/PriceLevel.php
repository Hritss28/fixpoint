<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceLevel extends Model
{
    protected $fillable = [
        'product_id',
        'level_type',
        'min_quantity',
        'price',
        'is_active'
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Level type constants
     */
    public const LEVEL_RETAIL = 'retail';
    public const LEVEL_WHOLESALE = 'wholesale';
    public const LEVEL_CONTRACTOR = 'contractor';
    public const LEVEL_DISTRIBUTOR = 'distributor';

    /**
     * Relationship with Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Scope for active price levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific level type
     */
    public function scopeForLevel($query, string $levelType)
    {
        return $query->where('level_type', $levelType);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get price for customer type and quantity
     */
    public static function getPriceForCustomer(int $productId, string $customerType, int $quantity = 1): ?float
    {
        $priceLevel = self::where('product_id', $productId)
            ->where('level_type', $customerType)
            ->where('min_quantity', '<=', $quantity)
            ->where('is_active', true)
            ->orderBy('min_quantity', 'desc')
            ->first();

        return $priceLevel ? $priceLevel->price : null;
    }

    /**
     * Get all price levels for a product
     */
    public static function getPriceLevelsForProduct(int $productId): array
    {
        $levels = self::where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get()
            ->groupBy('level_type');

        $result = [];
        foreach ($levels as $levelType => $priceLevels) {
            $result[$levelType] = $priceLevels->map(function ($level) {
                return [
                    'min_quantity' => $level->min_quantity,
                    'price' => $level->price,
                    'formatted_price' => $level->formatted_price,
                ];
            })->toArray();
        }

        return $result;
    }

    /**
     * Set price level for product
     */
    public static function setPriceLevel(
        int $productId, 
        string $levelType, 
        int $minQuantity, 
        float $price
    ): self {
        return self::updateOrCreate(
            [
                'product_id' => $productId,
                'level_type' => $levelType,
                'min_quantity' => $minQuantity,
            ],
            [
                'price' => $price,
                'is_active' => true,
            ]
        );
    }

    /**
     * Calculate discount percentage compared to retail
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        $retailPrice = self::where('product_id', $this->product_id)
            ->where('level_type', self::LEVEL_RETAIL)
            ->where('is_active', true)
            ->value('price');

        if (!$retailPrice || $retailPrice <= 0) {
            return null;
        }

        return (($retailPrice - $this->price) / $retailPrice) * 100;
    }

    /**
     * Get all available level types
     */
    public static function getAvailableLevelTypes(): array
    {
        return [
            self::LEVEL_RETAIL => 'Retail',
            self::LEVEL_WHOLESALE => 'Grosir',
            self::LEVEL_CONTRACTOR => 'Kontraktor',
            self::LEVEL_DISTRIBUTOR => 'Distributor',
        ];
    }
}
