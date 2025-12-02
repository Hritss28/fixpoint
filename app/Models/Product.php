<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStockMovements;
use App\Traits\HasPriceLevels;

class Product extends Model
{
    use HasFactory, HasStockMovements, HasPriceLevels;

    protected $reviewsCountCache = null;
    protected $averageRatingCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'original_price',
        'discount',
        'image',
        'stock',
        'sku',
        'is_active',
        'is_featured',
        'is_new',
        'weight',
        'rating',
        'review_count',
        'brand_id',
        'category_id',
        // Building store fields
        'unit',
        'min_order_qty',
        'wholesale_price',
        'contractor_price',
        'supplier_id',
        'reorder_level',
        'location',
        'barcode',
        'is_bulk_only',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
        // Building store casts
        'min_order_qty' => 'integer',
        'wholesale_price' => 'decimal:2',
        'contractor_price' => 'decimal:2',
        'reorder_level' => 'integer',
        'is_bulk_only' => 'boolean',
    ];

    /**
     * Get the category that owns the product.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get users who have this product in their wishlist.
     */
    public function wishlists()
    {
        return $this->belongsToMany(User::class, 'wishlist_items');
    }

    /**
     * Get the formatted price attribute.
     *
     * @return string
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get the formatted original price attribute.
     *
     * @return string
     */
    public function getFormattedOriginalPriceAttribute()
    {
        return 'Rp ' . number_format($this->original_price, 0, ',', '.');
    }

    /**
     * Check if product is on sale.
     *
     * @return bool
     */
    public function getIsOnSaleAttribute()
    {
        return $this->original_price > $this->price;
    }

    /**
     * Check if product is in stock.
     *
     * @return bool
     */
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Scope a query to only include active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include new products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    /**
     * Scope a query to only include products on sale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSale($query)
    {
        return $query->whereNotNull('original_price')
                    ->whereColumn('original_price', '>', 'price');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function getAverageRatingAttribute()
    {
        if ($this->averageRatingCache === null) {
            $this->averageRatingCache = $this->reviews()->avg('rating') ?: 0;
        }
        return $this->averageRatingCache;
    }

    /**
     * Get reviews count attribute dengan caching.
     *
     * @return int
     */
    public function getReviewsCountAttribute()
    {
        if ($this->reviewsCountCache === null) {
            $this->reviewsCountCache = $this->reviews()->count();
        }
        return $this->reviewsCountCache;
    }

    // ===== BUILDING STORE ENHANCEMENTS =====

    /**
     * Relationship with Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship with Stock Movements
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relationship with Price Levels
     */
    public function priceLevels()
    {
        return $this->hasMany(PriceLevel::class);
    }

    /**
     * Get price for customer type and quantity
     */
    public function getPriceForCustomer(string $customerType, int $quantity = 1): float
    {
        // First check price levels
        $priceLevel = PriceLevel::getPriceForCustomer($this->id, $customerType, $quantity);
        
        if ($priceLevel) {
            return $priceLevel;
        }

        // Fallback to product-specific prices
        return match ($customerType) {
            'wholesale' => $this->wholesale_price ?? $this->price,
            'contractor' => $this->contractor_price ?? $this->wholesale_price ?? $this->price,
            'distributor' => $this->contractor_price ?? $this->wholesale_price ?? $this->price,
            default => $this->price,
        };
    }

    /**
     * Get formatted wholesale price
     */
    public function getFormattedWholesalePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->wholesale_price ?? 0, 0, ',', '.');
    }

    /**
     * Get formatted contractor price
     */
    public function getFormattedContractorPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->contractor_price ?? 0, 0, ',', '.');
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->reorder_level;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'reorder_level');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Scope for bulk only products
     */
    public function scopeBulkOnly($query)
    {
        return $query->where('is_bulk_only', true);
    }

    /**
     * Scope by supplier
     */
    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Update stock
     */
    public function updateStock(int $quantity, string $type = 'adjustment', array $movementData = []): void
    {
        $oldStock = $this->stock;
        
        if ($type === 'out') {
            $this->decrement('stock', $quantity);
        } else {
            $this->increment('stock', $quantity);
        }

        // Create stock movement record
        StockMovement::create([
            'product_id' => $this->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit' => $this->unit,
            'notes' => "Stock updated from {$oldStock} to {$this->fresh()->stock}",
            'user_id' => auth()->id(),
            ...$movementData
        ]);
    }

    /**
     * Get all available price levels for this product
     */
    public function getAllPriceLevels(): array
    {
        return PriceLevel::getPriceLevelsForProduct($this->id);
    }
}