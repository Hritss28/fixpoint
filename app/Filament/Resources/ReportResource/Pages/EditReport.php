<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_preview')
                ->label('Generate Preview')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Preview Generated')
                        ->body('A preview of this report has been generated.')
                        ->info()
                        ->send();
                }),
                
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
