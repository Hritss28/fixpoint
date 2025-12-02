<?php

namespace App\Traits;

use App\Models\PriceLevel;
use App\Services\PriceCalculator;

trait HasPriceLevels
{
    /**
     * Relationship with price levels
     */
    public function priceLevels()
    {
        return $this->hasMany(PriceLevel::class, 'product_id');
    }
    
    /**
     * Get active price levels
     */
    public function activePriceLevels()
    {
        return $this->priceLevels()->where('is_active', true);
    }
    
    /**
     * Get price levels grouped by customer type
     */
    public function getPriceLevelsByType(): array
    {
        $priceLevels = $this->activePriceLevels()
            ->orderBy('min_quantity')
            ->get()
            ->groupBy('level_type');
        
        $grouped = [];
        foreach ($priceLevels as $type => $levels) {
            $grouped[$type] = $levels->toArray();
        }
        
        return $grouped;
    }
    
    /**
     * Get price for specific customer type and quantity
     */
    public function getPriceFor(string $customerType, int $quantity = 1): float
    {
        $priceCalculator = app(PriceCalculator::class);
        $calculation = $priceCalculator->calculatePrice($this->id, $customerType, $quantity);
        
        return $calculation['final_price'];
    }
    
    /**
     * Get all available prices for this product
     */
    public function getAllPrices(): array
    {
        $priceCalculator = app(PriceCalculator::class);
        return $priceCalculator->getPriceLevels($this->id);
    }
    
    /**
     * Get price comparison for different quantities
     */
    public function getPriceComparison(int $quantity = 1): array
    {
        $priceCalculator = app(PriceCalculator::class);
        return $priceCalculator->getPriceComparison($this->id, $quantity);
    }
    
    /**
     * Add or update price level
     */
    public function setPriceLevel(
        string $levelType, 
        int $minQuantity, 
        float $price, 
        bool $isActive = true
    ): PriceLevel {
        return $this->priceLevels()->updateOrCreate(
            [
                'level_type' => $levelType,
                'min_quantity' => $minQuantity,
            ],
            [
                'price' => $price,
                'is_active' => $isActive,
            ]
        );
    }
    
    /**
     * Get lowest price available for this product
     */
    public function getLowestPrice(): float
    {
        $lowestPriceLevel = $this->activePriceLevels()
            ->orderBy('price', 'asc')
            ->first();
        
        return $lowestPriceLevel ? $lowestPriceLevel->price : $this->price;
    }
    
    /**
     * Get highest discount percentage
     */
    public function getMaxDiscountPercent(): float
    {
        $lowestPrice = $this->getLowestPrice();
        
        if ($this->price <= 0) {
            return 0;
        }
        
        return round((($this->price - $lowestPrice) / $this->price) * 100, 2);
    }
    
    /**
     * Check if product has wholesale pricing
     */
    public function hasWholesalePricing(): bool
    {
        return $this->activePriceLevels()
            ->whereIn('level_type', ['wholesale', 'contractor', 'distributor'])
            ->exists();
    }
    
    /**
     * Get minimum quantity for wholesale price
     */
    public function getWholesaleMinQuantity(): ?int
    {
        $wholesaleLevel = $this->activePriceLevels()
            ->whereIn('level_type', ['wholesale', 'contractor', 'distributor'])
            ->orderBy('min_quantity', 'asc')
            ->first();
        
        return $wholesaleLevel ? $wholesaleLevel->min_quantity : null;
    }
    
    /**
     * Scope for products with wholesale pricing
     */
    public function scopeHasWholesalePricing($query)
    {
        return $query->whereHas('activePriceLevels', function ($q) {
            $q->whereIn('level_type', ['wholesale', 'contractor', 'distributor']);
        });
    }
}
