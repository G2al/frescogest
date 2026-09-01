<?php

namespace App\Filament\Resources\SpecialPriceRules\Tables;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SpecialPriceRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Regola')->searchable()->sortable(),
                TextColumn::make('audience')
                    ->label('Destinatario')
                    ->badge()
                    ->formatStateUsing(fn (SpecialPriceAudience $state): string => $state->label())
                    ->color(fn (SpecialPriceAudience $state): string => $state->color()),
                TextColumn::make('partner.name')->label('Partner')->placeholder('Tutti'),
                TextColumn::make('scope_type')
                    ->label('Ambito')
                    ->badge()
                    ->formatStateUsing(fn (SpecialPriceScope $state): string => $state->label()),
                TextColumn::make('target_label')->label('Applicata a'),
                TextColumn::make('markup_percentage')->label('Ricarico')->suffix('%')->sortable(),
                ToggleColumn::make('active')->label('Attiva')->sortable(),
            ])
            ->filters([
                SelectFilter::make('audience')->label('Destinatario')->options(SpecialPriceAudience::options()),
                SelectFilter::make('scope_type')->label('Ambito')->options(SpecialPriceScope::options()),
                TernaryFilter::make('active')->label('Stato')->placeholder('Tutte')->trueLabel('Attive')->falseLabel('Disattivate'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Elimina definitivamente'),
                ]),
            ]);
    }
}
