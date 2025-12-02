<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\OrderResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '10s';
    
    protected function getStats(): array
    {
        // Calculate total products and stock value
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereColumn('stock', '<=', 'min_stock')->count();
        $outOfStockProducts = Product::where('stock', 0)->count();
        
        // Calculate total stock value
        $totalStockValue = Product::selectRaw('SUM(stock * price) as total')->first()->total ?? 0;
        
        // Recent orders stats
        $todayOrders = Order::whereDate('created_at', today())->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        
        // Recent stock movements
        $todayMovements = StockMovement::whereDate('created_at', today())->count();
        
        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Active products in inventory')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->url(ProductResource::getUrl('index')),
                
            Stat::make('Low Stock Items', $lowStockProducts)
                ->description('Products below minimum stock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockProducts > 0 ? 'warning' : 'success')
                ->url(ProductResource::getUrl('index', [
                    'tableFilters' => [
                        'stock_status' => ['value' => 'low_stock']
                    ]
                ])),
                
            Stat::make('Out of Stock', $outOfStockProducts)
                ->description('Products with zero stock')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockProducts > 0 ? 'danger' : 'success')
                ->url(ProductResource::getUrl('index', [
                    'tableFilters' => [
                        'stock_status' => ['value' => 'out_of_stock']
                    ]
                ])),
                
            Stat::make('Stock Value', 'Rp ' . number_format($totalStockValue, 0, ',', '.'))
                ->description('Total inventory value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Today Orders', $todayOrders)
                ->description('Orders placed today')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->url(OrderResource::getUrl('index')),
                
            Stat::make('Pending Orders', $pendingOrders)
                ->description('Orders awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 5 ? 'warning' : 'info')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => ['pending']]
                    ]
                ])),
        ];
    }
}
