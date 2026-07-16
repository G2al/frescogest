<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Actions\DeliveryDocumentActions;
use App\Filament\Resources\Orders\Actions\OrderDeleteAction;
use App\Filament\Resources\Orders\Actions\OrderPaymentActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...OrderPaymentActions::make(),
            ...DeliveryDocumentActions::make(),
            OrderDeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] === 'confirmed' && blank($data['confirmed_at'] ?? null)) {
            $data['confirmed_at'] = now();
        }

        if ($data['status'] === 'delivered' && blank($data['delivered_at'] ?? null)) {
            $data['delivered_at'] = now();
        }

        return $data;
    }
}
