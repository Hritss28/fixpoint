<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockManager
{
    /**
     * Record stock in (increase stock)
     * 
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType (purchase|adjustment|return)
     * @param int|null $referenceId
     * @param string|null $notes
     * @param int|null $userId
     * @return StockMovement
     */
    public function stockIn(
        int $productId, 
        int $quantity, 
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $notes, $userId) {
            $product = Product::findOrFail($productId);
            
            // Create stock movement record
            $stockMovement = StockMovement::create([
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => $quantity,
                'unit' => $product->unit ?? 'pcs',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => $userId ?? auth()->id(),
                'previous_stock' => $product->current_stock ?? 0,
                'new_stock' => ($product->current_stock ?? 0) + $quantity,
            ]);
            
            // Update product current stock
            $product->increment('current_stock', $quantity);
            
            Log::info("Stock IN: Product {$productId}, Qty: +{$quantity}, New Stock: {$stockMovement->new_stock}");
            
            return $stockMovement;
        });
    }
    
    /**
     * Record stock out (decrease stock)
     * 
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType (order|adjustment|damage)
     * @param int|null $referenceId
     * @param string|null $notes
     * @param int|null $userId
     * @param bool $allowNegative
     * @return StockMovement
     * @throws \Exception
     */
    public function stockOut(
        int $productId,
        int $quantity,
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null,
        bool $allowNegative = false
    ): StockMovement {
        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $notes, $userId, $allowNegative) {
            $product = Product::findOrFail($productId);
            $currentStock = $product->current_stock ?? 0;
            
            // Check if stock is sufficient
            if (!$allowNegative && $currentStock < $quantity) {
                throw new \Exception(
                    "Insufficient stock for product '{$product->name}'. " .
                    "Current stock: {$currentStock}, Required: {$quantity}"
                );
            }
            
            $newStock = $currentStock - $quantity;
            
            // Create stock movement record
            $stockMovement = StockMovement::create([
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $quantity,
                'unit' => $product->unit ?? 'pcs',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => $userId ?? auth()->id(),
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
            ]);
            
            // Update product current stock
            $product->update(['current_stock' => $newStock]);
            
            Log::info("Stock OUT: Product {$productId}, Qty: -{$quantity}, New Stock: {$newStock}");
            
            return $stockMovement;
        });
    }
    
    /**
     * Record stock adjustment (can be positive or negative)
     * 
     * @param int $productId
     * @param int $actualStock The actual counted stock
     * @param string|null $reason
     * @param string|null $notes
     * @param int|null $userId
     * @return StockMovement|null
     */
    public function stockAdjustment(
        int $productId,
        int $actualStock,
        ?string $reason = null,
        ?string $notes = null,
        ?int $userId = null
    ): ?StockMovement {
        return DB::transaction(function () use ($productId, $actualStock, $reason, $notes, $userId) {
            $product = Product::findOrFail($productId);
            $currentStock = $product->current_stock ?? 0;
            $difference = $actualStock - $currentStock;
            
            // No adjustment needed if stock is already correct
            if ($difference == 0) {
                return null;
            }
            
            $adjustmentNotes = "Stock adjustment: {$reason}. " . 
                              "System stock: {$currentStock}, Actual count: {$actualStock}, " .
                              "Difference: {$difference}. {$notes}";
            
            // Create stock movement record
            $stockMovement = StockMovement::create([
                'product_id' => $productId,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'unit' => $product->unit ?? 'pcs',
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'notes' => $adjustmentNotes,
                'user_id' => $userId ?? auth()->id(),
                'previous_stock' => $currentStock,
                'new_stock' => $actualStock,
                'adjustment_type' => $difference > 0 ? 'increase' : 'decrease',
            ]);
            
            // Update product current stock
            $product->update(['current_stock' => $actualStock]);
            
            Log::warning("Stock ADJUSTMENT: Product {$productId}, Difference: {$difference}, New Stock: {$actualStock}");
            
            return $stockMovement;
        });
    }
    
    /**
     * Get current stock for a product
     * 
     * @param int $productId
     * @return int
     */
    public function getCurrentStock(int $productId): int
    {
        $product = Product::find($productId);
        return $product ? ($product->current_stock ?? 0) : 0;
    }
    
    /**
     * Check if stock is available for given quantity
     * 
     * @param int $productId
     * @param int $requiredQuantity
     * @return bool
     */
    public function validateStockAvailability(int $productId, int $requiredQuantity): bool
    {
        $currentStock = $this->getCurrentStock($productId);
        return $currentStock >= $requiredQuantity;
    }
    
    /**
     * Get stock movements for a product
     * 
     * @param int $productId
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockMovements(int $productId, int $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        return StockMovement::with(['user', 'product'])
            ->where('product_id', $productId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get products with low stock
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts()
    {
        return Product::whereNotNull('reorder_level')
            ->whereRaw('COALESCE(current_stock, 0) <= reorder_level')
            ->where('is_active', true)
            ->with(['supplier'])
            ->get();
    }
    
    /**
     * Check if product needs reordering
     * 
     * @param int $productId
     * @return bool
     */
    public function needsReordering(int $productId): bool
    {
        $product = Product::find($productId);
        
        if (!$product || !$product->reorder_level) {
            return false;
        }
        
        return ($product->current_stock ?? 0) <= $product->reorder_level;
    }
    
    /**
     * Reserve stock for an order (without moving stock out yet)
     * 
     * @param int $productId
     * @param int $quantity
     * @param int $orderId
     * @return StockMovement
     */
    public function reserveStock(int $productId, int $quantity, int $orderId): StockMovement
    {
        return DB::transaction(function () use ($productId, $quantity, $orderId) {
            $product = Product::findOrFail($productId);
            $currentStock = $product->current_stock ?? 0;
            
            // Check if stock is sufficient for reservation
            if ($currentStock < $quantity) {
                throw new \Exception(
                    "Insufficient stock to reserve for product '{$product->name}'. " .
                    "Current stock: {$currentStock}, Required: {$quantity}"
                );
            }
            
            // Create reservation record (not affecting current_stock yet)
            $stockMovement = StockMovement::create([
                'product_id' => $productId,
                'type' => 'reserved',
                'quantity' => $quantity,
                'unit' => $product->unit ?? 'pcs',
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'notes' => "Stock reserved for order #{$orderId}",
                'user_id' => auth()->id(),
                'previous_stock' => $currentStock,
                'new_stock' => $currentStock, // Stock not moved yet, just reserved
                'is_reserved' => true,
            ]);
            
            Log::info("Stock RESERVED: Product {$productId}, Qty: {$quantity} for Order #{$orderId}");
            
            return $stockMovement;
        });
    }
    
    /**
     * Release reserved stock (cancel reservation)
     * 
     * @param int $productId
     * @param int $orderId
     * @return bool
     */
    public function releaseReservedStock(int $productId, int $orderId): bool
    {
        return DB::transaction(function () use ($productId, $orderId) {
            $reservation = StockMovement::where('product_id', $productId)
                ->where('reference_type', 'order')
                ->where('reference_id', $orderId)
                ->where('type', 'reserved')
                ->where('is_reserved', true)
                ->first();
                
            if ($reservation) {
                $reservation->update(['is_reserved' => false]);
                
                Log::info("Stock reservation RELEASED: Product {$productId} for Order #{$orderId}");
                return true;
            }
            
            return false;
        });
    }
    
    /**
     * Convert reserved stock to actual stock out
     * 
     * @param int $productId
     * @param int $orderId
     * @return StockMovement|null
     */
    public function fulfillReservedStock(int $productId, int $orderId): ?StockMovement
    {
        return DB::transaction(function () use ($productId, $orderId) {
            $reservation = StockMovement::where('product_id', $productId)
                ->where('reference_type', 'order')
                ->where('reference_id', $orderId)
                ->where('type', 'reserved')
                ->where('is_reserved', true)
                ->first();
                
            if ($reservation) {
                // Mark reservation as fulfilled
                $reservation->update(['is_reserved' => false]);
                
                // Create actual stock out movement
                $stockOut = $this->stockOut(
                    $productId,
                    $reservation->quantity,
                    'order',
                    $orderId,
                    "Stock fulfilled from reservation for order #{$orderId}",
                    $reservation->user_id,
                    false
                );
                
                Log::info("Reserved stock FULFILLED: Product {$productId} for Order #{$orderId}");
                
                return $stockOut;
            }
            
            return null;
        });
    }
    
    /**
     * Get stock summary for dashboard
     * 
     * @return array
     */
    public function getStockSummary(): array
    {
        $totalProducts = Product::where('is_active', true)->count();
        $lowStockCount = $this->getLowStockProducts()->count();
        $outOfStockCount = Product::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('current_stock')
                      ->orWhere('current_stock', '<=', 0);
            })
            ->count();
            
        $totalStockValue = Product::where('is_active', true)
            ->selectRaw('SUM(COALESCE(current_stock, 0) * price) as total_value')
            ->value('total_value') ?? 0;
            
        return [
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_stock_value' => $totalStockValue,
            'stock_turnover_rate' => $this->calculateStockTurnoverRate(),
        ];
    }
    
    /**
     * Calculate stock turnover rate (approximate)
     * 
     * @return float
     */
    private function calculateStockTurnoverRate(): float
    {
        // Get stock movements in last 30 days
        $stockOutMovements = StockMovement::where('type', 'out')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('quantity');
            
        $averageStock = Product::where('is_active', true)
            ->avg('current_stock') ?? 1;
            
        return $averageStock > 0 ? round($stockOutMovements / $averageStock, 2) : 0;
    }
}
