<?php

namespace App\Filament\Resources\CostCategories\Pages;

use App\Filament\Resources\CostCategories\CostCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCostCategory extends EditRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
