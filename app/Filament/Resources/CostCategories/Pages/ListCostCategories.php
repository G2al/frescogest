<?php

namespace App\Filament\Resources\CostCategories\Pages;

use App\Filament\Resources\CostCategories\CostCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCostCategories extends ListRecords
{
    protected static string $resource = CostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
