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
                TextColumn::make('name')->label('Articolo')->searchable()->sortable(),
                TextColumn::make('code')->label('Codice')->searchable()->sortable(),
                TextColumn::make('brand')->label('Marca')->searchable()->toggleable(),
                TextColumn::make('productCategory.name')->label('Categoria')->sortable(),
                TextColumn::make('variants_count')->label('Varianti')->counts('variants')->sortable(),
                TextColumn::make('purchase_cost_per_unit_gross')->label('Costo')->money('EUR')->sortable(),
                TextColumn::make('base_price_per_unit')->label('Prezzo')->money('EUR')->sortable(),
                ToggleColumn::make('active')->label('Disponibile')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Disponibilità')
                    ->placeholder('Tutti')
                    ->trueLabel('Disponibili')
                    ->falseLabel('Non disponibili'),
                SelectFilter::make('product_category_id')
                    ->label('Categoria')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()->label('Eliminati'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Rendi disponibili')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Rendi non disponibili')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
