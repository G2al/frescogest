<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Codice')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('productCategory.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('taxRate.name')
                    ->label('Aliquota IVA')
                    ->sortable(),
                TextColumn::make('defaultUnitOfMeasure.symbol')
                    ->label('Unità')
                    ->sortable(),
                TextColumn::make('price_per_kg')
                    ->label('Prezzo base')
                    ->money('EUR')
                    ->suffix('/kg')
                    ->sortable(),
                TextColumn::make('active')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attivo' : 'Non attivo')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('is_public')
                    ->label('Catalogo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Pubblico' : 'Nascosto')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Attivi')
                    ->falseLabel('Non attivi'),
                TrashedFilter::make()
                    ->label('Eliminati'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
