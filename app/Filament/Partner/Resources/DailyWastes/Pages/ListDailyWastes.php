<?php

namespace App\Filament\Partner\Resources\DailyWastes\Pages;

use App\Filament\Partner\Resources\DailyWastes\DailyWasteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyWastes extends ListRecords
{
    protected static string $resource = DailyWasteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Registra scarto')];
    }
}
