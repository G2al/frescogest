<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Elimina definitivamente')
                ->modalHeading('Eliminare definitivamente il prodotto?')
                ->modalDescription('Il prodotto verrà rimosso dal database. Lo storico degli ordini manterrà i dati già registrati.')
                ->modalSubmitActionLabel('Elimina definitivamente'),
        ];
    }
}
