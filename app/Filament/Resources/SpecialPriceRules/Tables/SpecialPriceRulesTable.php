<?php

namespace App\Filament\Resources\SpecialPriceRules\Tables;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Models\SpecialPriceRule;
use App\Services\Pricing\SpecialPriceRuleApplier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
            ->recordActions([
                Action::make('apply')
                    ->label('Applica')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Applicare questa regola?')
                    ->modalDescription('Il ricarico viene scritto subito sui prodotti nell’ambito di questa regola (o sul listino del partner specifico, se indicato), sovrascrivendo eventuali personalizzazioni già presenti.')
                    ->modalSubmitActionLabel('Applica ora')
                    ->action(function (SpecialPriceRule $record): void {
                        $count = app(SpecialPriceRuleApplier::class)->apply($record);

                        Notification::make()
                            ->title("Regola applicata a {$count} ".($count === 1 ? 'prodotto' : 'prodotti'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Elimina definitivamente'),
                ]),
            ]);
    }
}
