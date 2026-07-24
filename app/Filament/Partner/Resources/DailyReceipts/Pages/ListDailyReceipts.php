<?php

namespace App\Filament\Partner\Resources\DailyReceipts\Pages;

use App\Filament\Partner\Resources\DailyReceipts\DailyReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyReceipts extends ListRecords
{
    protected static string $resource = DailyReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Registra incasso')];
    }
}
