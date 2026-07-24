<?php

namespace App\Filament\Partner\Resources\DailyReceipts\Pages;

use App\Filament\Partner\Resources\DailyReceipts\DailyReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyReceipt extends EditRecord
{
    protected static string $resource = DailyReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
