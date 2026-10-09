<?php

namespace App\Filament\Resources\UnitOfMeasures\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnitOfMeasuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol')
                    ->label('Simbolo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('active')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attiva' : 'Non attiva')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Stato')
                    ->placeholder('Tutte')
                    ->trueLabel('Attive')
                    ->falseLabel('Non attive'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('I prodotti che usano queste unità non verranno eliminati: perderanno solo l\'unità di misura assegnata e resteranno "da ricategorizzare" finché non li riassegni tu.')
                        ->before(function ($records): void {
                            $unitIds = collect($records)->pluck('id');

                            $names = Product::query()
                                ->whereIn('default_unit_of_measure_id', $unitIds)
                                ->pluck('name')
                                ->all();

                            session()->flash('orphaned_unit_product_names', $names);
                        })
                        ->after(function (): void {
                            $names = session()->pull('orphaned_unit_product_names', []);

                            if ($names === []) {
                                return;
                            }

                            Notification::make()
                                ->title(count($names).' prodotti hanno perso l\'unità di misura')
                                ->body(implode(', ', $names))
                                ->warning()
                                ->persistent()
                                ->send();
                        }),
                ]),
            ]);
    }
}
