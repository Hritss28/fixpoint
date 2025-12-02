<?php

namespace App\Filament\Resources\DashboardSettingResource\Pages;

use App\Filament\Resources\DashboardSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Setting;

class ListDashboardSettings extends ListRecords
{
    protected static string $resource = DashboardSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Setting')
                ->icon('heroicon-o-plus'),
                
            Action::make('seed_defaults')
                ->label('Seed Default Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Seed Default Settings')
                ->modalDescription('This will create default system settings. Existing settings will not be overwritten.')
                ->action(function () {
                    $defaultSettings = [
                        [
                            'key' => 'app_name',
                            'label' => 'Application Name',
                            'type' => 'string',
                            'value' => 'Fixpoint - Toko Material',
                            'category' => 'general',
                            'description' => 'The name of the application displayed in the interface',
                            'default_value' => 'Fixpoint - Toko Material',
                            'is_public' => true,
                            'is_system' => true,
                        ],
                        [
                            'key' => 'company_address',
                            'label' => 'Company Address',
                            'type' => 'string',
                            'value' => 'Jl. Contoh No. 123, Jakarta',
                            'category' => 'general',
                            'description' => 'Company address for invoices and documents',
                            'default_value' => '',
                            'is_public' => true,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'company_phone',
                            'label' => 'Company Phone',
                            'type' => 'string',
                            'value' => '+62-21-1234567',
                            'category' => 'general',
                            'description' => 'Company phone number for contact information',
                            'default_value' => '',
                            'is_public' => true,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'default_currency',
                            'label' => 'Default Currency',
                            'type' => 'string',
                            'value' => 'IDR',
                            'category' => 'general',
                            'description' => 'Default currency for pricing and transactions',
                            'default_value' => 'IDR',
                            'is_public' => true,
                            'is_system' => true,
                        ],
                        [
                            'key' => 'low_stock_threshold',
                            'label' => 'Low Stock Threshold',
                            'type' => 'number',
                            'value' => '10',
                            'category' => 'notifications',
                            'description' => 'Alert when stock falls below this number',
                            'default_value' => '10',
                            'is_public' => false,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'enable_credit_alerts',
                            'label' => 'Enable Credit Alerts',
                            'type' => 'boolean',
                            'value' => '1',
                            'category' => 'notifications',
                            'description' => 'Send alerts for overdue payments and credit limits',
                            'default_value' => '1',
                            'is_public' => false,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'max_credit_utilization',
                            'label' => 'Maximum Credit Utilization %',
                            'type' => 'number',
                            'value' => '80',
                            'category' => 'security',
                            'description' => 'Maximum allowed credit utilization percentage',
                            'default_value' => '80',
                            'is_public' => false,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'invoice_footer',
                            'label' => 'Invoice Footer Text',
                            'type' => 'string',
                            'value' => 'Terima kasih atas kepercayaan Anda',
                            'category' => 'appearance',
                            'description' => 'Footer text displayed on invoices',
                            'default_value' => 'Terima kasih atas kepercayaan Anda',
                            'is_public' => true,
                            'is_system' => false,
                        ],
                        [
                            'key' => 'report_settings',
                            'label' => 'Report Generation Settings',
                            'type' => 'json',
                            'value' => '{"format": "pdf", "include_charts": true, "date_format": "d/m/Y"}',
                            'category' => 'reports',
                            'description' => 'Default settings for report generation',
                            'default_value' => '{"format": "pdf", "include_charts": true, "date_format": "d/m/Y"}',
                            'is_public' => false,
                            'is_system' => false,
                        ],
                    ];
                    
                    $created = 0;
                    $existing = 0;
                    
                    foreach ($defaultSettings as $settingData) {
                        $setting = Setting::firstOrCreate(
                            ['key' => $settingData['key']],
                            $settingData
                        );
                        
                        if ($setting->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $existing++;
                        }
                    }
                    
                    Notification::make()
                        ->title('Default Settings Seeded')
                        ->body("Created {$created} new settings. {$existing} settings already existed.")
                        ->success()
                        ->send();
                }),
                
            Action::make('backup_settings')
                ->label('Backup Settings')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $settings = Setting::all();
                    $fileName = 'settings_backup_' . now()->format('Y-m-d_H-i-s') . '.json';
                    $filePath = storage_path('app/public/backups/' . $fileName);
                    
                    if (!file_exists(dirname($filePath))) {
                        mkdir(dirname($filePath), 0755, true);
                    }
                    
                    file_put_contents($filePath, json_encode($settings->toArray(), JSON_PRETTY_PRINT));
                    
                    Notification::make()
                        ->title('Settings Backed Up')
                        ->body("Settings backed up to {$fileName}")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download')
                                ->url(asset('storage/backups/' . $fileName))
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                }),
        ];
    }
}
