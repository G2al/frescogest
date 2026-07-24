<?php

namespace App\Filament\Partner\Resources\DailyWastes\Pages;

use App\Filament\Partner\Resources\DailyWastes\DailyWasteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyWaste extends CreateRecord
{
    protected static string $resource = DailyWasteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['partner_id'] = auth('admin')->user()->partner->getKey();

        return $data;
    }
}
