<?php

namespace App\Filament\Partner\Resources\DailyWastes\Pages;

use App\Filament\Partner\Resources\DailyWastes\DailyWasteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyWaste extends EditRecord
{
    protected static string $resource = DailyWasteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
