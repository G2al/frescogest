<?php

namespace App\Filament\Resources\PromotionCodes\Tables;

use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromotionCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Promozione')->searchable()->sortable(),
                TextColumn::make('code')->label('Codice')->badge()->copyable()->searchable(),
                TextColumn::make('discount_percentage')->label('Sconto')->suffix('%')->sortable(),
                TextColumn::make('audience')
                    ->label('Destinatari')
                    ->badge()
                    ->formatStateUsing(fn (PromotionAudience $state): string => $state->label()),
                TextColumn::make('rule')
                    ->label('Regola')
                    ->formatStateUsing(fn (PromotionRule $state): string => $state->label()),
                TextColumn::make('starts_at')->label('Dal')->dateTime('d/m/Y H:i')->placeholder('Subito')->sortable(),
                TextColumn::make('ends_at')->label('Fino al')->dateTime('d/m/Y H:i')->placeholder('Senza scadenza')->sortable(),
                TextColumn::make('usages_count')->label('Utilizzi')->counts('usages')->sortable(),
                IconColumn::make('single_use_per_customer')->label('Uso singolo')->boolean(),
                ToggleColumn::make('active')->label('Attivo'),
            ])
            ->filters([
                SelectFilter::make('audience')->label('Destinatari')->options(PromotionAudience::options()),
                SelectFilter::make('rule')->label('Regola')->options(PromotionRule::options()),
                TernaryFilter::make('active')->label('Stato')->trueLabel('Attivi')->falseLabel('Disattivati'),
            ])
            ->defaultSort('created_at', 'desc')
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
