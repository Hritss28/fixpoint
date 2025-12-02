<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Report')
                ->icon('heroicon-o-plus'),
                
            Action::make('quick_reports')
                ->label('Quick Reports')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->dropdown()
                ->dropdownActions([
                    Action::make('daily_sales')
                        ->label('Today\'s Sales')
                        ->icon('heroicon-o-currency-dollar')
                        ->action(fn () => $this->generateQuickReport('sales', 'today')),
                        
                    Action::make('weekly_inventory')
                        ->label('This Week\'s Inventory')
                        ->icon('heroicon-o-cube')
                        ->action(fn () => $this->generateQuickReport('inventory', 'this_week')),
                        
                    Action::make('monthly_receivables')
                        ->label('Monthly Receivables')
                        ->icon('heroicon-o-banknotes')
                        ->action(fn () => $this->generateQuickReport('receivables', 'this_month')),
                        
                    Action::make('overdue_payments')
                        ->label('Overdue Payments')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->action(fn () => $this->generateOverdueReport()),
                ]),
        ];
    }
    
    protected function generateQuickReport(string $type, string $dateRange): void
    {
        // Generate quick report logic here
        \Filament\Notifications\Notification::make()
            ->title('Quick Report Generated')
            ->body("A {$type} report for {$dateRange} has been generated.")
            ->success()
            ->send();
    }
    
    protected function generateOverdueReport(): void
    {
        // Generate overdue report logic here
        \Filament\Notifications\Notification::make()
            ->title('Overdue Payments Report Generated')
            ->body('A report of all overdue payments has been generated.')
            ->warning()
            ->send();
    }
}
