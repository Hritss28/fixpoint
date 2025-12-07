<?php

namespace App\Filament\Resources\DeliveryNoteResource\Pages;

use App\Filament\Resources\DeliveryNoteResource;
use App\Models\DeliveryNote;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryNote extends CreateRecord
{
    protected static string $resource = DeliveryNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate delivery number
        $data['delivery_number'] = DeliveryNote::generateDeliveryNumber();
        
        // Set customer_id from order
        if (isset($data['order_id'])) {
            $order = Order::find($data['order_id']);
            if ($order) {
                $data['customer_id'] = $order->user_id;
            }
        }
        
        return $data;
    }
}
