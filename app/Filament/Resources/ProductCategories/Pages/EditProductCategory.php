<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Models\ProductCategory;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        $orphanedProductNames = [];

        return [
            DeleteAction::make()
                ->label('Elimina definitivamente')
                ->modalHeading('Eliminare definitivamente la categoria?')
                ->modalDescription('I prodotti di questa categoria non verranno eliminati: perderanno solo la categoria assegnata e resteranno "senza categoria" finché non li riassegni tu.')
                ->modalSubmitActionLabel('Elimina definitivamente')
                ->before(function (ProductCategory $record) use (&$orphanedProductNames): void {
                    $orphanedProductNames = $record->products()->pluck('name')->all();
                })
                ->after(function () use (&$orphanedProductNames): void {
                    if ($orphanedProductNames === []) {
                        return;
                    }

                    Notification::make()
                        ->title(count($orphanedProductNames).' prodotti hanno perso la categoria')
                        ->body(implode(', ', $orphanedProductNames))
                        ->warning()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
