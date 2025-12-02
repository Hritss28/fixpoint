<?php

namespace App\Filament\Resources\CustomerCreditResource\Pages;

use App\Filament\Resources\CustomerCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerCredits extends ListRecords
{
    protected static string $resource = CustomerCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
