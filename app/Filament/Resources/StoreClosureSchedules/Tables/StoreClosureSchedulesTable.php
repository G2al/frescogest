<?php

namespace App\Filament\Resources\StoreClosureSchedules\Tables;

use App\Enums\StoreClosureType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreClosureSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Chiusura')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (StoreClosureType $state): string => $state->label()),
                TextColumn::make('schedule_description')
                    ->label('Quando')
                    ->wrap(),
                TextColumn::make('starts_at')
                    ->label('Dalle')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Alle')
                    ->time('H:i')
                    ->sortable(),
                ToggleColumn::make('active')
                    ->label('Attiva'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(StoreClosureType::options()),
                TernaryFilter::make('active')
                    ->label('Stato')
                    ->placeholder('Tutte')
                    ->trueLabel('Attive')
                    ->falseLabel('Disattivate'),
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
