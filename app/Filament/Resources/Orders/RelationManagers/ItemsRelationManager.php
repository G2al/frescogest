<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\OrderItem;
use App\Models\UnitOfMeasure;
use App\Services\Orders\OrderItemSnapshotService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Righe ordine';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Prodotto')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                // Il prodotto si sceglie solo in creazione: in modifica resta fisso,
                // per correggere una riga cambia quantità/prezzo/unità/IVA, non il
                // prodotto (per cambiarlo si elimina la riga e se ne aggiunge una nuova).
                ->disabled(fn (?OrderItem $record): bool => $record !== null)
                ->dehydrated(),
            TextInput::make('quantity')
                ->label('Quantità')
                ->numeric()
                ->minValue(0.001)
                ->step(0.001)
                ->required(),
            Select::make('unit_of_measure_id')
                ->label('Unità di misura')
                ->options(fn (): array => UnitOfMeasure::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->helperText('Vale solo per questa riga, non cambia l’unità predefinita del prodotto.'),
            TextInput::make('unit_price_net')
                ->label('Prezzo netto')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix('€')
                ->helperText('Lascia vuoto per usare il prezzo di listino del cliente.'),
            TextInput::make('tax_percentage')
                ->label('IVA')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->suffix('%')
                ->helperText('Lascia vuoto per usare l’aliquota del prodotto.'),
            TextInput::make('purchase_cost_per_unit_net')
                ->label('Prezzo di costo')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix('€')
                ->helperText('Lascia vuoto per usare il costo attuale del prodotto.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')->label('Prodotto')->searchable(),
                TextColumn::make('quantity')->label('Quantità')->numeric(decimalPlaces: 3),
                TextColumn::make('unit_of_measure_symbol')->label('Unità'),
                TextColumn::make('unit_price_net')->label('Prezzo netto')->money('EUR'),
                TextColumn::make('tax_percentage')->label('IVA')->suffix('%'),
                TextColumn::make('line_net')->label('Netto')->money('EUR'),
                TextColumn::make('line_gross')->label('IVA inclusa')->money('EUR'),
                TextColumn::make('margin_amount')->label('Margine')->money('EUR'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Aggiungi prodotto')
                    // enrichManual (non enrich) perché in admin, come nella creazione di
                    // una bolla/ordine manuale, non va applicata la quantità minima:
                    // quel vincolo esiste solo per chi prenota dal sito pubblico.
                    ->mutateDataUsing(fn (array $data): array => app(OrderItemSnapshotService::class)->enrichManual($data, $this->getOwnerRecord()))
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->fillForm(fn (OrderItem $record): array => [
                        'product_id' => $record->product_id,
                        'quantity' => $record->quantity,
                        'unit_of_measure_id' => UnitOfMeasure::query()->where('symbol', $record->unit_of_measure_symbol)->value('id'),
                        'unit_price_net' => $record->unit_price_net,
                        'tax_percentage' => $record->tax_percentage,
                        'purchase_cost_per_unit_net' => $record->purchase_cost_is_custom ? $record->purchase_cost_per_unit_net : null,
                    ])
                    ->mutateDataUsing(fn (array $data): array => app(OrderItemSnapshotService::class)->enrichManual($data, $this->getOwnerRecord()))
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
                DeleteAction::make()
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
            ]);
    }
}
