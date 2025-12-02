<?php

namespace App\Filament\Widgets;

use App\Models\PaymentTerm;
use App\Models\Order;
use App\Filament\Resources\PaymentTermResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentTrackingWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Payment statistics
        $overduePayments = PaymentTerm::where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();
            
        $dueSoon = PaymentTerm::whereBetween('due_date', [now(), now()->addDays(7)])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();
            
        $totalOutstanding = PaymentTerm::whereNotIn('status', ['paid', 'cancelled'])
            ->sum('amount') - PaymentTerm::whereNotIn('status', ['paid', 'cancelled'])
            ->sum('paid_amount');
            
        $todayPayments = PaymentTerm::whereDate('payment_date', today())
            ->where('status', 'paid')
            ->sum('paid_amount');
            
        // Recent payment activity
        $recentPayments = PaymentTerm::where('payment_date', '>=', now()->subDays(7))
            ->where('status', 'paid')
            ->count();
        
        return [
            Stat::make('Overdue Payments', $overduePayments)
                ->description('Payments past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overduePayments > 0 ? 'danger' : 'success')
                ->url(PaymentTermResource::getUrl('index', [
                    'tableFilters' => [
                        'overdue' => ['isActive' => true]
                    ]
                ])),
                
            Stat::make('Due in 7 Days', $dueSoon)
                ->description('Payments due soon')
                ->descriptionIcon('heroicon-m-clock')
                ->color($dueSoon > 5 ? 'warning' : 'info')
                ->url(PaymentTermResource::getUrl('index', [
                    'tableFilters' => [
                        'due_soon' => ['isActive' => true]
                    ]
                ])),
                
            Stat::make('Outstanding Amount', 'Rp ' . number_format($totalOutstanding, 0, ',', '.'))
                ->description('Total unpaid amount')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalOutstanding > 1000000 ? 'warning' : 'info'),
                
            Stat::make('Today\'s Payments', 'Rp ' . number_format($todayPayments, 0, ',', '.'))
                ->description('Payments received today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Recent Payments', $recentPayments)
                ->description('Payments in last 7 days')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info')
                ->url(PaymentTermResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => ['paid']]
                    ]
                ])),
        ];
    }
}
