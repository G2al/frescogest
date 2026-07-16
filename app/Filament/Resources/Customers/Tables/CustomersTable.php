<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Cliente')
                    ->searchable(['company_name', 'first_name', 'last_name'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('company_name', $direction)
                        ->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction)),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('vat_number')
                    ->label('Partita IVA')
                    ->searchable()
                    ->toggleable(),
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
            ->defaultSort('company_name')
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
