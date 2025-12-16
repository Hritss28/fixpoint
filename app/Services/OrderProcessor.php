<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\DeliveryNote;
use App\Services\StockManager;
use App\Services\PriceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderProcessor
{
    protected StockManager $stockManager;
    protected PriceCalculator $priceCalculator;
    
    public function __construct(
        StockManager $stockManager,
        PriceCalculator $priceCalculator
    ) {
        $this->stockManager = $stockManager;
        $this->priceCalculator = $priceCalculator;
    }
    
    /**
     * Process a new order
     * 
     * @param array $orderData
     * @return Order
     */
    public function processOrder(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            // Validate and prepare order data
            $validatedData = $this->validateOrderData($orderData);
            
            // Calculate order totals
            $orderCalculation = $this->calculateOrderTotals(
                $validatedData['items'], 
                $validatedData['customer_id'],
                $validatedData['customer_type']
            );
            
            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $validatedData['customer_id'],
                'customer_type' => $validatedData['customer_type'],
                'status' => 'pending',
                'total_amount' => $orderCalculation['grand_total'],
                'subtotal' => $orderCalculation['subtotal'],
                'tax_amount' => $orderCalculation['tax_amount'],
                'discount_amount' => $orderCalculation['order_discount_amount'],
                'payment_method' => $validatedData['payment_method'],
                'payment_status' => 'paid', // Assuming Midtrans handles this or it's pending until callback
                'project_name' => $validatedData['project_name'] ?? null,
                'notes' => $validatedData['notes'] ?? null,
            ]);
            
            // Create order items and reserve stock
            foreach ($validatedData['items'] as $itemData) {
                $this->createOrderItem($order->id, $itemData, $validatedData['customer_type']);
                
                // Reserve stock for the order
                $this->stockManager->reserveStock(
                    $itemData['product_id'],
                    $itemData['quantity'],
                    $order->id
                );
            }
            
            Log::info("Order processed successfully: Order {$order->order_number}, Total {$order->total_amount}");
            
            return $order->load(['orderItems.product', 'user']);
        });
    }
    
    /**
     * Validate order data
     * 
     * @param array $orderData
     * @return array
     */
    private function validateOrderData(array $orderData): array
    {
        // Required fields validation
        $required = ['customer_id', 'items', 'payment_method'];
        foreach ($required as $field) {
            if (!isset($orderData[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate customer
        $customer = User::findOrFail($orderData['customer_id']);
        
        // Validate items
        if (empty($orderData['items']) || !is_array($orderData['items'])) {
            throw new \Exception("Order must have at least one item");
        }
        
        $validatedItems = [];
        foreach ($orderData['items'] as $item) {
            $validatedItems[] = $this->validateOrderItem($item);
        }
        
        // Validate payment method
        $allowedPaymentMethods = ['cash', 'transfer'];
        if (!in_array($orderData['payment_method'], $allowedPaymentMethods)) {
            // Allow other methods if needed, but 'tempo' is removed
            // throw new \Exception("Invalid payment method: " . $orderData['payment_method']);
        }
        
        return [
            'customer_id' => $customer->id,
            'customer_type' => $customer->customer_type ?? 'retail',
            'items' => $validatedItems,
            'payment_method' => $orderData['payment_method'],
            'project_name' => $orderData['project_name'] ?? null,
            'notes' => $orderData['notes'] ?? null,
        ];
    }
    
    /**
     * Validate individual order item
     * 
     * @param array $itemData
     * @return array
     */
    private function validateOrderItem(array $itemData): array
    {
        // Required item fields
        if (!isset($itemData['product_id']) || !isset($itemData['quantity'])) {
            throw new \Exception("Each item must have product_id and quantity");
        }
        
        $product = Product::findOrFail($itemData['product_id']);
        
        if (!$product->is_active) {
            throw new \Exception("Product '{$product->name}' is not active");
        }
        
        $quantity = (int) $itemData['quantity'];
        if ($quantity <= 0) {
            throw new \Exception("Quantity must be greater than 0");
        }
        
        // Check minimum order quantity
        if ($product->min_order_qty && $quantity < $product->min_order_qty) {
            throw new \Exception(
                "Minimum order quantity for '{$product->name}' is {$product->min_order_qty}"
            );
        }
        
        // Check stock availability
        if (!$this->stockManager->validateStockAvailability($product->id, $quantity)) {
            $currentStock = $this->stockManager->getCurrentStock($product->id);
            throw new \Exception(
                "Insufficient stock for '{$product->name}'. Available: {$currentStock}, Required: {$quantity}"
            );
        }
        
        return [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->price, // Will be recalculated with proper pricing
        ];
    }
    
    /**
     * Calculate order totals with proper pricing
     * 
     * @param array $items
     * @param int $customerId
     * @param string $customerType
     * @return array
     */
    public function calculateOrderTotals(array $items, int $customerId, string $customerType): array
    {
        $orderItems = array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        }, $items);
        
        return $this->priceCalculator->calculateOrderTotal($orderItems, $customerId, $customerType);
    }
    
    /**
     * Create order item with proper pricing
     * 
     * @param int $orderId
     * @param array $itemData
     * @param string $customerType
     * @return OrderItem
     */
    private function createOrderItem(int $orderId, array $itemData, string $customerType): OrderItem
    {
        $priceCalculation = $this->priceCalculator->calculatePrice(
            $itemData['product_id'],
            $customerType,
            $itemData['quantity']
        );
        
        return OrderItem::create([
            'order_id' => $orderId,
            'product_id' => $itemData['product_id'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $priceCalculation['final_price'],
            'total_price' => $priceCalculation['line_total'],
        ]);
    }
    
    /**
     * Generate unique order number
     * 
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        
        // Get today's order count
        $todayOrderCount = Order::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('ORD%s%04d', $date, $todayOrderCount);
    }
    
    /**
     * Cancel order and release reserved stock
     * 
     * @param int $orderId
     * @param string|null $reason
     * @return Order
     */
    public function cancelOrder(int $orderId, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::findOrFail($orderId);
            
            if (in_array($order->status, ['shipped', 'delivered', 'completed'])) {
                throw new \Exception("Cannot cancel order {$order->order_number} - already processed");
            }
            
            // Release reserved stock
            foreach ($order->orderItems as $item) {
                $this->stockManager->releaseReservedStock($item->product_id, $order->id);
            }
            
            // Update order status
            $order->update([
                'status' => 'cancelled',
                'notes' => ($order->notes ?? '') . "\nCancelled: " . ($reason ?? 'No reason provided'),
            ]);
            
            Log::info("Order cancelled: {$order->order_number}. Reason: {$reason}");
            
            return $order;
        });
    }
    
    /**
     * Process order for shipping (convert reserved stock to actual stock out)
     * 
     * @param int $orderId
     * @return Order
     */
    public function processForShipping(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::findOrFail($orderId);
            
            if ($order->status !== 'confirmed') {
                // throw new \Exception("Order {$order->order_number} is not confirmed yet");
                // Relaxed check for now as we removed approval step
            }
            
            // Convert reserved stock to actual stock out
            foreach ($order->orderItems as $item) {
                $this->stockManager->fulfillReservedStock($item->product_id, $order->id);
            }
            
            // Update order status
            $order->update(['status' => 'processing']);
            
            Log::info("Order processing for shipping: {$order->order_number}");
            
            return $order;
        });
    }
    
    /**
     * Create delivery note for order
     * 
     * @param int $orderId
     * @param array $deliveryData
     * @return DeliveryNote
     */
    public function createDeliveryNote(int $orderId, array $deliveryData): DeliveryNote
    {
        $order = Order::findOrFail($orderId);
        
        if (!in_array($order->status, ['processing', 'confirmed'])) {
            throw new \Exception("Order {$order->order_number} is not ready for delivery");
        }
        
        $deliveryNote = DeliveryNote::create([
            'delivery_number' => $this->generateDeliveryNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->user_id,
            'delivery_date' => $deliveryData['delivery_date'] ?? now(),
            'driver_name' => $deliveryData['driver_name'] ?? null,
            'vehicle_number' => $deliveryData['vehicle_number'] ?? null,
            'status' => 'pending',
            'notes' => $deliveryData['notes'] ?? null,
        ]);
        
        // Update order status
        $order->update(['status' => 'ready_for_delivery']);
        
        return $deliveryNote;
    }
    
    /**
     * Generate delivery number
     * 
     * @return string
     */
    private function generateDeliveryNumber(): string
    {
        $date = now()->format('Ymd');
        $count = DeliveryNote::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('DN%s%04d', $date, $count);
    }
    
    /**
     * Get order summary for dashboard
     * 
     * @return array
     */
    public function getOrderSummary(): array
    {
        $today = now()->toDateString();
        
        return [
            'today_orders' => Order::whereDate('created_at', $today)->count(),

            'pending_payment' => Order::where('payment_status', 'pending')->count(),
            'ready_for_delivery' => Order::where('status', 'ready_for_delivery')->count(),
            'today_revenue' => Order::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
        ];
    }
}
