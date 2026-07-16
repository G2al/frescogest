<?php

namespace App\Filament\Resources\ProductCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('active')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attiva' : 'Non attiva')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('is_public')
                    ->label('Catalogo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Pubblica' : 'Nascosta')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Stato')
                    ->placeholder('Tutte')
                    ->trueLabel('Attive')
                    ->falseLabel('Non attive'),
                TrashedFilter::make()
                    ->label('Eliminate'),
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
