<?php

namespace App\Filament\Resources\ProductCategories\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Immagine')
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('catalog_color')
                    ->label('Colore'),
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
                TextColumn::make('auto_markup_enabled')
                    ->label('Ricalcolo prezzo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Automatico' : 'Manuale')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
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
                        ->label('Elimina definitivamente')
                        ->modalHeading('Eliminare definitivamente le categorie selezionate?')
                        ->modalDescription('I prodotti di queste categorie non verranno eliminati: perderanno solo la categoria assegnata e resteranno "senza categoria" finché non li riassegni tu.')
                        ->modalSubmitActionLabel('Elimina definitivamente')
                        ->before(function ($records): void {
                            $categoryIds = collect($records)->pluck('id');

                            $names = Product::query()
                                ->whereIn('product_category_id', $categoryIds)
                                ->pluck('name')
                                ->all();

                            session()->flash('orphaned_product_names', $names);
                        })
                        ->after(function (): void {
                            $names = session()->pull('orphaned_product_names', []);

                            if ($names === []) {
                                return;
                            }

                            Notification::make()
                                ->title(count($names).' prodotti hanno perso la categoria')
                                ->body(implode(', ', $names))
                                ->warning()
                                ->persistent()
                                ->send();
                        }),
                ]),
            ]);
    }
}
