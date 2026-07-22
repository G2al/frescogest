<?php

namespace App\Filament\Resources\CommercialRules\Tables;

use App\Enums\CustomerType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommercialRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Regola')->searchable()->sortable(),
            TextColumn::make('customer_type')->label('Cliente')->badge()->formatStateUsing(fn (CustomerType $state) => $state->label()),
            TextColumn::make('province')->label('Provincia')->placeholder('Tutta Italia'),
            TextColumn::make('minimum_order_gross')->label('Spesa minima')->money('EUR')->sortable(),
            TextColumn::make('free_shipping_threshold_gross')->label('Gratis da')->money('EUR')->placeholder('—'),
            TextColumn::make('shipping_fee_net')->label('Consegna netta')->money('EUR'),
            IconColumn::make('active')->label('Attiva')->boolean(),
        ])->filters([
            SelectFilter::make('customer_type')->label('Tipo cliente')->options(CustomerType::options()),
        ])->defaultSort('name')->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
