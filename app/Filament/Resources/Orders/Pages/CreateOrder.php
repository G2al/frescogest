<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        $this->record->update([
            'order_number' => 'IPF-'.str_pad((string) $this->record->id, 6, '0', STR_PAD_LEFT),
        ]);
    }
}
