<?php

namespace App\Filament\Resources\TaxRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TaxRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('percentage')
                    ->label('Percentuale')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable(),
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
            ->defaultSort('percentage')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
