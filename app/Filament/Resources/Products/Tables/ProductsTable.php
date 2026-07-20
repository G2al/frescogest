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
                TextColumn::make('purchase_cost_per_unit')
                    ->label('Costo netto')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('purchase_cost_gross')
                    ->label('Costo IVA incl.')
                    ->money('EUR'),
                TextColumn::make('base_price_per_unit')
                    ->label('Listino base')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('markup_percentage')
                    ->label('Ricarico')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('restaurant_price_per_unit')
                    ->label('Listino ristoratori')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('active')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attivo' : 'Non attivo')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
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
