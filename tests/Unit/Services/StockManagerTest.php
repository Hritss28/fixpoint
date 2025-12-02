<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\StockMovement;
use App\Services\StockManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockManagerTest extends TestCase
{
    use RefreshDatabase;
    
    private StockManager $stockManager;
    private Product $product;
    private User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockManager = app(StockManager::class);
        
        // Create test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100000,
            'current_stock' => 50,
            'unit' => 'pcs',
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs($this->user);
    }
    
    public function test_stock_in_increases_stock(): void
    {
        $initialStock = $this->product->current_stock;
        $quantity = 20;
        
        $stockMovement = $this->stockManager->stockIn(
            $this->product->id,
            $quantity,
            'purchase',
            null,
            'Test stock in'
        );
        
        $this->product->refresh();
        
        $this->assertEquals($initialStock + $quantity, $this->product->current_stock);
        $this->assertEquals('in', $stockMovement->type);
        $this->assertEquals($quantity, $stockMovement->quantity);
        $this->assertEquals($initialStock, $stockMovement->previous_stock);
        $this->assertEquals($initialStock + $quantity, $stockMovement->new_stock);
    }
    
    public function test_stock_out_decreases_stock(): void
    {
        $initialStock = $this->product->current_stock;
        $quantity = 15;
        
        $stockMovement = $this->stockManager->stockOut(
            $this->product->id,
            $quantity,
            'order',
            123,
            'Test stock out'
        );
        
        $this->product->refresh();
        
        $this->assertEquals($initialStock - $quantity, $this->product->current_stock);
        $this->assertEquals('out', $stockMovement->type);
        $this->assertEquals($quantity, $stockMovement->quantity);
        $this->assertEquals($initialStock, $stockMovement->previous_stock);
        $this->assertEquals($initialStock - $quantity, $stockMovement->new_stock);
    }
    
    public function test_stock_out_prevents_negative_stock(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');
        
        // Try to take out more than available
        $this->stockManager->stockOut(
            $this->product->id,
            100, // More than current stock (50)
            'order'
        );
    }
    
    public function test_stock_out_allows_negative_when_permitted(): void
    {
        $initialStock = $this->product->current_stock;
        $quantity = 80; // More than available
        
        $stockMovement = $this->stockManager->stockOut(
            $this->product->id,
            $quantity,
            'adjustment',
            null,
            'Emergency stock out',
            null,
            true // Allow negative
        );
        
        $this->product->refresh();
        
        $this->assertEquals($initialStock - $quantity, $this->product->current_stock);
        $this->assertLessThan(0, $this->product->current_stock);
    }
    
    public function test_stock_adjustment_corrects_stock(): void
    {
        $actualStock = 35; // Different from current 50
        
        $stockMovement = $this->stockManager->stockAdjustment(
            $this->product->id,
            $actualStock,
            'Physical count discrepancy'
        );
        
        $this->product->refresh();
        
        $this->assertEquals($actualStock, $this->product->current_stock);
        $this->assertEquals('adjustment', $stockMovement->type);
        $this->assertEquals(15, $stockMovement->quantity); // Difference
        $this->assertEquals('decrease', $stockMovement->adjustment_type);
    }
    
    public function test_stock_adjustment_returns_null_when_no_difference(): void
    {
        $actualStock = $this->product->current_stock; // Same as current
        
        $stockMovement = $this->stockManager->stockAdjustment(
            $this->product->id,
            $actualStock
        );
        
        $this->assertNull($stockMovement);
    }
    
    public function test_get_current_stock_returns_correct_value(): void
    {
        $currentStock = $this->stockManager->getCurrentStock($this->product->id);
        
        $this->assertEquals($this->product->current_stock, $currentStock);
    }
    
    public function test_validate_stock_availability(): void
    {
        $this->assertTrue($this->stockManager->validateStockAvailability($this->product->id, 30));
        $this->assertFalse($this->stockManager->validateStockAvailability($this->product->id, 100));
    }
    
    public function test_needs_reordering_when_below_reorder_level(): void
    {
        // Set stock below reorder level
        $this->product->update(['current_stock' => 5, 'reorder_level' => 10]);
        
        $this->assertTrue($this->stockManager->needsReordering($this->product->id));
    }
    
    public function test_needs_reordering_returns_false_when_above_reorder_level(): void
    {
        // Set stock above reorder level
        $this->product->update(['current_stock' => 20, 'reorder_level' => 10]);
        
        $this->assertFalse($this->stockManager->needsReordering($this->product->id));
    }
    
    public function test_reserve_stock_creates_reservation(): void
    {
        $quantity = 10;
        $orderId = 123;
        
        $reservation = $this->stockManager->reserveStock($this->product->id, $quantity, $orderId);
        
        $this->assertEquals('reserved', $reservation->type);
        $this->assertEquals($quantity, $reservation->quantity);
        $this->assertEquals($orderId, $reservation->reference_id);
        $this->assertTrue($reservation->is_reserved);
        
        // Stock should not be affected yet
        $this->product->refresh();
        $this->assertEquals(50, $this->product->current_stock);
    }
    
    public function test_release_reserved_stock(): void
    {
        $orderId = 123;
        
        // First reserve stock
        $this->stockManager->reserveStock($this->product->id, 10, $orderId);
        
        // Then release it
        $released = $this->stockManager->releaseReservedStock($this->product->id, $orderId);
        
        $this->assertTrue($released);
        
        // Check reservation is marked as not reserved
        $reservation = StockMovement::where('reference_id', $orderId)
            ->where('type', 'reserved')
            ->first();
            
        $this->assertFalse($reservation->is_reserved);
    }
    
    public function test_fulfill_reserved_stock(): void
    {
        $quantity = 10;
        $orderId = 123;
        
        // First reserve stock
        $this->stockManager->reserveStock($this->product->id, $quantity, $orderId);
        
        // Then fulfill it
        $stockOut = $this->stockManager->fulfillReservedStock($this->product->id, $orderId);
        
        $this->assertNotNull($stockOut);
        $this->assertEquals('out', $stockOut->type);
        $this->assertEquals($quantity, $stockOut->quantity);
        
        // Stock should now be reduced
        $this->product->refresh();
        $this->assertEquals(40, $this->product->current_stock);
        
        // Reservation should be marked as fulfilled
        $reservation = StockMovement::where('reference_id', $orderId)
            ->where('type', 'reserved')
            ->first();
            
        $this->assertFalse($reservation->is_reserved);
    }
    
    public function test_get_low_stock_products(): void
    {
        // Create another product with low stock
        $lowStockProduct = Product::create([
            'name' => 'Low Stock Product',
            'price' => 50000,
            'current_stock' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        
        $lowStockProducts = $this->stockManager->getLowStockProducts();
        
        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals($lowStockProduct->id, $lowStockProducts->first()->id);
    }
    
    public function test_get_stock_summary(): void
    {
        $summary = $this->stockManager->getStockSummary();
        
        $this->assertArrayHasKey('total_products', $summary);
        $this->assertArrayHasKey('low_stock_count', $summary);
        $this->assertArrayHasKey('out_of_stock_count', $summary);
        $this->assertArrayHasKey('total_stock_value', $summary);
        $this->assertArrayHasKey('stock_turnover_rate', $summary);
        
        $this->assertEquals(1, $summary['total_products']);
    }
}
