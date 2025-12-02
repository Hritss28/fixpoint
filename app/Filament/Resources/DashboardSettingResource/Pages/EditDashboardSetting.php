<?php

namespace App\Filament\Resources\DashboardSettingResource\Pages;

use App\Filament\Resources\DashboardSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDashboardSetting extends EditRecord
{
    protected static string $resource = DashboardSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_system),
                
            Actions\Action::make('reset_to_default')
                ->label('Reset to Default')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !empty($this->record->default_value))
                ->requiresConfirmation()
                ->modalHeading('Reset to Default Value')
                ->modalDescription('Are you sure you want to reset this setting to its default value? This action cannot be undone.')
                ->action(function () {
                    $this->record->update(['value' => $this->record->default_value]);
                    
                    Notification::make()
                        ->success()
                        ->title('Setting Reset')
                        ->body('Setting has been reset to its default value.')
                        ->send();
                        
                    $this->refreshFormData(['value']);
                }),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Setting Updated')
            ->body('The setting has been updated successfully.');
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle boolean values
        if ($data['type'] === 'boolean') {
            $data['value'] = $data['value'] ? '1' : '0';
        }
        
        // Handle JSON validation
        if ($data['type'] === 'json' && !empty($data['value'])) {
            $decoded = json_decode($data['value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Notification::make()
                    ->danger()
                    ->title('Invalid JSON')
                    ->body('The JSON value is not valid. Please check the syntax.')
                    ->persistent()
                    ->send();
                    
                $this->halt();
            }
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Clear any application cache if needed
        if (function_exists('cache')) {
            cache()->forget('settings_' . $this->record->key);
        }
    }
}
