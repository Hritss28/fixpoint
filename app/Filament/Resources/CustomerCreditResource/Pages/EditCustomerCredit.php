<?php

namespace App\Filament\Resources\CustomerCreditResource\Pages;

use App\Filament\Resources\CustomerCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerCredit extends EditRecord
{
    protected static string $resource = CustomerCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
