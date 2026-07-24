<?php

namespace App\Filament\Partner\Resources\DailyReceipts\Pages;

use App\Filament\Partner\Resources\DailyReceipts\DailyReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyReceipt extends CreateRecord
{
    protected static string $resource = DailyReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['partner_id'] = auth('admin')->user()->partner->getKey();

        return $data;
    }
}
