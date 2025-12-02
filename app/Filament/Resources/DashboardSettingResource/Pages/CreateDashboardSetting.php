<?php

namespace App\Filament\Resources\DashboardSettingResource\Pages;

use App\Filament\Resources\DashboardSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDashboardSetting extends CreateRecord
{
    protected static string $resource = DashboardSettingResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Setting Created')
            ->body('The new setting has been created successfully.');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
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
}
