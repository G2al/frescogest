<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Elimina definitivamente')
                ->modalHeading('Eliminare definitivamente la categoria?')
                ->modalDescription('Verranno eliminati definitivamente anche tutti i prodotti appartenenti alla categoria.')
                ->modalSubmitActionLabel('Elimina definitivamente'),
        ];
    }
}
