<?php

namespace App\Filament\Resources\StoreClosureSchedules\Pages;

use App\Filament\Resources\StoreClosureSchedules\StoreClosureScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreClosureSchedules extends ListRecords
{
    protected static string $resource = StoreClosureScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
