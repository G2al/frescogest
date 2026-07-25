<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Services\Customers\DeleteCustomerService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
            ])
            ->defaultSort('company_name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deletePermanently')
                        ->label('Elimina definitivamente')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminare definitivamente i clienti selezionati?')
                        ->modalDescription('Verranno eliminati anche gli account, gli ordini e tutti i dati collegati. L’operazione non può essere annullata.')
                        ->modalSubmitActionLabel('Elimina definitivamente')
                        ->action(function (Collection $records): void {
                            app(DeleteCustomerService::class)->deleteMany($records);

                            Notification::make()
                                ->success()
                                ->title('Clienti eliminati definitivamente')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
