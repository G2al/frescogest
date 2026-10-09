<?php

namespace App\Filament\Resources\UnitOfMeasures\Pages;

use App\Filament\Resources\UnitOfMeasures\UnitOfMeasureResource;
use App\Models\UnitOfMeasure;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUnitOfMeasure extends EditRecord
{
    protected static string $resource = UnitOfMeasureResource::class;

    protected function getHeaderActions(): array
    {
        $orphanedProductNames = [];

        return [
            DeleteAction::make()
                ->modalDescription('I prodotti che usano questa unità non verranno eliminati: perderanno solo l\'unità di misura assegnata e resteranno "da ricategorizzare" finché non li riassegni tu.')
                ->before(function (UnitOfMeasure $record) use (&$orphanedProductNames): void {
                    $orphanedProductNames = $record->products()->pluck('name')->all();
                })
                ->after(function () use (&$orphanedProductNames): void {
                    if ($orphanedProductNames === []) {
                        return;
                    }

                    Notification::make()
                        ->title(count($orphanedProductNames).' prodotti hanno perso l\'unità di misura')
                        ->body(implode(', ', $orphanedProductNames))
                        ->warning()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
