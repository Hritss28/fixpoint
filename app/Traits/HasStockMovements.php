<?php

namespace App\Traits;

use App\Models\StockMovement;
use App\Services\StockManager;

trait HasStockMovements
{
    /**
     * Relationship with stock movements
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }
    
    /**
     * Get recent stock movements
     */
    public function recentStockMovements(int $limit = 10)
    {
        return $this->stockMovements()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }
    
    /**
     * Stock in method
     */
    public function stockIn(
        int $quantity, 
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): StockMovement {
        $stockManager = app(StockManager::class);
        return $stockManager->stockIn(
            $this->id, 
            $quantity, 
            $referenceType, 
            $referenceId, 
            $notes, 
            $userId
        );
    }
    
    /**
     * Stock out method
     */
    public function stockOut(
        int $quantity,
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null,
        bool $allowNegative = false
    ): StockMovement {
        $stockManager = app(StockManager::class);
        return $stockManager->stockOut(
            $this->id,
            $quantity,
            $referenceType,
            $referenceId,
            $notes,
            $userId,
            $allowNegative
        );
    }
    
    /**
     * Stock adjustment method
     */
    public function adjustStock(
        int $actualStock,
        ?string $reason = null,
        ?string $notes = null,
        ?int $userId = null
    ): ?StockMovement {
        $stockManager = app(StockManager::class);
        return $stockManager->stockAdjustment(
            $this->id,
            $actualStock,
            $reason,
            $notes,
            $userId
        );
    }
    
    /**
     * Reserve stock for order
     */
    public function reserveStock(int $quantity, int $orderId): StockMovement
    {
        $stockManager = app(StockManager::class);
        return $stockManager->reserveStock($this->id, $quantity, $orderId);
    }
    
    /**
     * Check if stock is available
     */
    public function hasStockAvailable(int $quantity): bool
    {
        $stockManager = app(StockManager::class);
        return $stockManager->validateStockAvailability($this->id, $quantity);
    }
    
    /**
     * Get current stock (with latest calculation)
     */
    public function getCurrentStock(): int
    {
        $stockManager = app(StockManager::class);
        return $stockManager->getCurrentStock($this->id);
    }
    
    /**
     * Check if product needs reordering
     */
    public function needsReordering(): bool
    {
        $stockManager = app(StockManager::class);
        return $stockManager->needsReordering($this->id);
    }
    
    /**
     * Get stock status
     */
    public function getStockStatus(): string
    {
        $currentStock = $this->current_stock ?? 0;
        $reorderLevel = $this->reorder_level ?? 0;
        
        if ($currentStock <= 0) {
            return 'out_of_stock';
        } elseif ($reorderLevel > 0 && $currentStock <= $reorderLevel) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }
    
    /**
     * Get stock status label
     */
    public function getStockStatusLabel(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }
    
    /**
     * Get stock status color for UI
     */
    public function getStockStatusColor(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning', 
            'in_stock' => 'success',
            default => 'secondary'
        };
    }
    
    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_level')
            ->whereRaw('COALESCE(current_stock, 0) <= reorder_level');
    }
    
    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where(function($q) {
            $q->whereNull('current_stock')
              ->orWhere('current_stock', '<=', 0);
        });
    }
    
    /**
     * Scope for in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }
}
