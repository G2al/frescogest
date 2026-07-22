<?php

namespace App\Filament\Resources\StoreClosureSchedules\Pages;

use App\Filament\Resources\StoreClosureSchedules\StoreClosureScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreClosureSchedule extends EditRecord
{
    protected static string $resource = StoreClosureScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
