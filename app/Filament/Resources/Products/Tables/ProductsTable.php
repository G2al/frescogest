<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                TextColumn::make('purchase_cost_per_unit_gross')
                    ->label('Costo IVA incl.')
                    ->money('EUR')
                    ->sortable(),
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
                ToggleColumn::make('active')
                    ->label('Attivo')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Attivi')
                    ->falseLabel('Non attivi'),
                SelectFilter::make('product_category_id')
                    ->label('Categoria')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()
                    ->label('Eliminati'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Attiva selezionati')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Disattiva selezionati')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
