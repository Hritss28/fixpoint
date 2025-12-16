<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PriceLevel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PriceCalculator
{
    /**
     * Customer type priority (higher number = better price)
     */
    private const CUSTOMER_TYPE_PRIORITY = [
        'retail' => 1,
        'wholesale' => 2,
        'contractor' => 3,
        'distributor' => 4,
    ];

    /**
     * Calculate price for a product based on customer type and quantity
     * 
     * @param int $productId
     * @param string $customerType
     * @param int $quantity
     * @param int|null $customerId
     * @return array
     */
    public function calculatePrice(
        int $productId, 
        string $customerType, 
        int $quantity = 1, 
        ?int $customerId = null
    ): array {
        $product = Product::findOrFail($productId);
        
        // Get applicable price level
        $priceLevel = $this->getApplicablePriceLevel($productId, $customerType, $quantity);
        
        // Base price from price level or product default
        $basePrice = $priceLevel ? $priceLevel->price : $product->price;
        
        // Apply customer-specific discount if any
        $customerDiscount = $customerId ? $this->getCustomerDiscount($customerId) : 0;
        
        // Calculate final price
        $discountAmount = $basePrice * ($customerDiscount / 100);
        $finalPrice = $basePrice - $discountAmount;
        
        // Calculate totals
        $lineTotal = $finalPrice * $quantity;
        
        Log::info("Price calculated for Product {$productId}: Base {$basePrice}, Final {$finalPrice}, Qty {$quantity}");
        
        return [
            'product_id' => $productId,
            'customer_type' => $customerType,
            'quantity' => $quantity,
            'base_price' => $basePrice,
            'customer_discount_percent' => $customerDiscount,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'line_total' => $lineTotal,
            'price_level' => $priceLevel ? [
                'id' => $priceLevel->id,
                'level_type' => $priceLevel->level_type,
                'min_quantity' => $priceLevel->min_quantity,
            ] : null,
            'savings' => $product->price - $finalPrice, // Savings from retail price
        ];
    }
    
    /**
     * Get applicable price level for product, customer type, and quantity
     * 
     * @param int $productId
     * @param string $customerType
     * @param int $quantity
     * @return PriceLevel|null
     */
    public function getApplicablePriceLevel(int $productId, string $customerType, int $quantity): ?PriceLevel
    {
        // Get all active price levels for this product
        $priceLevels = PriceLevel::where('product_id', $productId)
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc') // Highest quantity first
            ->get();
        
        if ($priceLevels->isEmpty()) {
            return null;
        }
        
        // First try to find exact customer type match with highest quantity
        $exactMatch = $priceLevels->where('level_type', $customerType)->first();
        
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // If no exact match, find the best available price level
        // Based on customer type priority (distributor > contractor > wholesale > retail)
        $customerTypePriority = self::CUSTOMER_TYPE_PRIORITY[$customerType] ?? 1;
        
        $bestPriceLevel = null;
        $bestPrice = PHP_FLOAT_MAX;
        
        foreach ($priceLevels as $priceLevel) {
            $levelTypePriority = self::CUSTOMER_TYPE_PRIORITY[$priceLevel->level_type] ?? 1;
            
            // Only consider price levels that customer is eligible for
            if ($levelTypePriority <= $customerTypePriority && $priceLevel->price < $bestPrice) {
                $bestPrice = $priceLevel->price;
                $bestPriceLevel = $priceLevel;
            }
        }
        
        return $bestPriceLevel;
    }
    
    /**
     * Get all price levels for a product
     * 
     * @param int $productId
     * @return array
     */
    public function getPriceLevels(int $productId): array
    {
        $product = Product::findOrFail($productId);
        
        $priceLevels = PriceLevel::where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get();
        
        $levels = [];
        
        // Add retail level (default price)
        $levels[] = [
            'level_type' => 'retail',
            'min_quantity' => 1,
            'price' => $product->price,
            'is_default' => true,
            'savings_percent' => 0,
        ];
        
        // Add configured price levels
        foreach ($priceLevels as $level) {
            $savingsPercent = $product->price > 0 ? 
                round((($product->price - $level->price) / $product->price) * 100, 2) : 0;
                
            $levels[] = [
                'level_type' => $level->level_type,
                'min_quantity' => $level->min_quantity,
                'price' => $level->price,
                'is_default' => false,
                'savings_percent' => $savingsPercent,
            ];
        }
        
        return $levels;
    }
    
    /**
     * Calculate total order amount with all applicable discounts
     * 
     * @param array $orderItems Array of [product_id, quantity]
     * @param int|null $customerId
     * @param string $customerType
     * @return array
     */
    public function calculateOrderTotal(array $orderItems, ?int $customerId = null, string $customerType = 'retail'): array
    {
        $subtotal = 0;
        $totalSavings = 0;
        $itemDetails = [];
        
        foreach ($orderItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            
            $priceCalculation = $this->calculatePrice($productId, $customerType, $quantity, $customerId);
            
            $itemDetails[] = $priceCalculation;
            $subtotal += $priceCalculation['line_total'];
            $totalSavings += $priceCalculation['savings'] * $quantity;
        }
        
        // Apply order-level discounts (volume discount, customer specific)
        $orderDiscount = $this->calculateOrderDiscount($subtotal, $customerId, $customerType);
        $orderDiscountAmount = $subtotal * ($orderDiscount / 100);
        
        // Calculate final totals
        $totalAfterDiscount = $subtotal - $orderDiscountAmount;
        $tax = $this->calculateTax($totalAfterDiscount);
        $grandTotal = $totalAfterDiscount + $tax;
        
        return [
            'items' => $itemDetails,
            'subtotal' => $subtotal,
            'item_savings' => $totalSavings,
            'order_discount_percent' => $orderDiscount,
            'order_discount_amount' => $orderDiscountAmount,
            'total_after_discount' => $totalAfterDiscount,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'total_savings' => $totalSavings + $orderDiscountAmount,
        ];
    }
    
    /**
     * Get customer-specific discount percentage
     * 
     * @param int $customerId
     * @return float
     */
    private function getCustomerDiscount(int $customerId): float
    {
        // Customer specific discounts are removed
        return 0;
    }
    
    /**
     * Calculate order-level discount based on total amount
     * 
     * @param float $orderTotal
     * @param int|null $customerId
     * @param string $customerType
     * @return float
     */
    private function calculateOrderDiscount(float $orderTotal, ?int $customerId = null, string $customerType = 'retail'): float
    {
        $discount = 0;
        
        // Volume-based discounts
        if ($orderTotal >= 50000000) { // 50M IDR
            $discount = 5; // 5% discount
        } elseif ($orderTotal >= 20000000) { // 20M IDR
            $discount = 3; // 3% discount  
        } elseif ($orderTotal >= 10000000) { // 10M IDR
            $discount = 2; // 2% discount
        }
        
        // Customer type additional discount
        $customerTypeDiscount = match($customerType) {
            'distributor' => 2,
            'contractor' => 1,
            'wholesale' => 0.5,
            default => 0
        };
        
        return $discount + $customerTypeDiscount;
    }
    
    /**
     * Calculate tax (can be customized based on business rules)
     * 
     * @param float $amount
     * @return float
     */
    private function calculateTax(float $amount): float
    {
        // PPN 11% in Indonesia (can be configurable)
        $taxRate = 0.11; 
        return $amount * $taxRate;
    }
    
    /**
     * Get price comparison for different customer types
     * 
     * @param int $productId
     * @param int $quantity
     * @return array
     */
    public function getPriceComparison(int $productId, int $quantity = 1): array
    {
        $comparisons = [];
        
        foreach (array_keys(self::CUSTOMER_TYPE_PRIORITY) as $customerType) {
            $priceCalc = $this->calculatePrice($productId, $customerType, $quantity);
            
            $comparisons[$customerType] = [
                'price' => $priceCalc['final_price'],
                'total' => $priceCalc['line_total'],
                'savings' => $priceCalc['savings'],
                'savings_percent' => $priceCalc['savings'] > 0 ? 
                    round(($priceCalc['savings'] / $priceCalc['base_price']) * 100, 2) : 0,
            ];
        }
        
        return $comparisons;
    }
    
    /**
     * Check if customer qualifies for a price level
     * 
     * @param string $customerType
     * @param string $requiredLevel
     * @return bool
     */
    public function customerQualifiesForPriceLevel(string $customerType, string $requiredLevel): bool
    {
        $customerPriority = self::CUSTOMER_TYPE_PRIORITY[$customerType] ?? 1;
        $requiredPriority = self::CUSTOMER_TYPE_PRIORITY[$requiredLevel] ?? 1;
        
        return $customerPriority >= $requiredPriority;
    }
    
    /**
     * Get cached price for performance
     * 
     * @param int $productId
     * @param string $customerType
     * @param int $quantity
     * @return array
     */
    public function getCachedPrice(int $productId, string $customerType, int $quantity = 1): array
    {
        $cacheKey = "price_calc_{$productId}_{$customerType}_{$quantity}";
        
        return Cache::remember($cacheKey, 300, function () use ($productId, $customerType, $quantity) {
            return $this->calculatePrice($productId, $customerType, $quantity);
        });
    }
    
    /**
     * Clear price cache for a product (when price levels change)
     * 
     * @param int $productId
     * @return void
     */
    public function clearPriceCache(int $productId): void
    {
        $customerTypes = array_keys(self::CUSTOMER_TYPE_PRIORITY);
        $quantities = [1, 10, 50, 100, 200]; // Common quantities
        
        foreach ($customerTypes as $type) {
            foreach ($quantities as $qty) {
                Cache::forget("price_calc_{$productId}_{$type}_{$qty}");
            }
        }
    }
}
