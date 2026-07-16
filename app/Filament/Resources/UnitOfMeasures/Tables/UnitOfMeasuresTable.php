<?php

namespace App\Filament\Resources\UnitOfMeasures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
