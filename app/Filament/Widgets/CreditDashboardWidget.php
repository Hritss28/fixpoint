<?php

namespace App\Filament\Widgets;

use App\Models\CustomerCredit;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Filament\Resources\CustomerCreditResource;
use App\Filament\Resources\PaymentTermResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditDashboardWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Credit statistics
        $totalCreditCustomers = User::where('credit_limit', '>', 0)->count();
        
        $totalCreditLimit = User::sum('credit_limit');
        
        $totalOutstandingDebt = PaymentTerm::whereNotIn('status', ['paid', 'cancelled'])
            ->sum('amount') - PaymentTerm::whereNotIn('status', ['paid', 'cancelled'])
            ->sum('paid_amount');
            
        $overdueDebt = PaymentTerm::where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('amount') - PaymentTerm::where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('paid_amount');
            
        $creditUtilization = $totalCreditLimit > 0 ? ($totalOutstandingDebt / $totalCreditLimit) * 100 : 0;
        
        // High-risk customers (over 80% utilization or overdue)
        $highRiskCustomers = User::where('credit_limit', '>', 0)
            ->where(function ($query) {
                $query->whereHas('paymentTerms', function ($q) {
                    $q->where('due_date', '<', now())
                      ->whereNotIn('status', ['paid', 'cancelled']);
                });
            })
            ->count();
        
        return [
            Stat::make('Credit Customers', $totalCreditCustomers)
                ->description('Customers with credit facilities')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->url(CustomerCreditResource::getUrl('index')),
                
            Stat::make('Total Credit Limit', 'Rp ' . number_format($totalCreditLimit, 0, ',', '.'))
                ->description('Total credit facilities extended')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
                
            Stat::make('Outstanding Debt', 'Rp ' . number_format($totalOutstandingDebt, 0, ',', '.'))
                ->description('Total unpaid receivables')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($totalOutstandingDebt > ($totalCreditLimit * 0.7) ? 'warning' : 'success')
                ->url(PaymentTermResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => ['pending', 'partial', 'overdue']]
                    ]
                ])),
                
            Stat::make('Overdue Debt', 'Rp ' . number_format($overdueDebt, 0, ',', '.'))
                ->description('Payments past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueDebt > 0 ? 'danger' : 'success')
                ->url(PaymentTermResource::getUrl('index', [
                    'tableFilters' => [
                        'overdue' => ['isActive' => true]
                    ]
                ])),
                
            Stat::make('Credit Utilization', number_format($creditUtilization, 1) . '%')
                ->description('Overall credit utilization rate')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($creditUtilization > 80 ? 'danger' : ($creditUtilization > 60 ? 'warning' : 'success')),
                
            Stat::make('High Risk Customers', $highRiskCustomers)
                ->description('Customers requiring attention')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($highRiskCustomers > 0 ? 'danger' : 'success')
                ->url(CustomerCreditResource::getUrl('index', [
                    'tableFilters' => [
                        'has_overdue' => ['isActive' => true]
                    ]
                ])),
        ];
    }
}
