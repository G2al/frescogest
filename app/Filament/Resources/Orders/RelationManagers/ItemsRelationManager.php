<?php

namespace App\Filament\Resources\Orders\RelationManagers;

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
                ->required(),
            TextInput::make('quantity')
                ->label('Quantità')
                ->numeric()
                ->minValue(0.001)
                ->step(0.001)
                ->required(),
        ]);
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
                    ->mutateDataUsing(fn (array $data): array => app(OrderItemSnapshotService::class)->enrich($data, $this->getOwnerRecord()))
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => app(OrderItemSnapshotService::class)->enrich($data, $this->getOwnerRecord()))
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
                DeleteAction::make()
                    ->after(fn () => app(OrderItemSnapshotService::class)->recalculate($this->getOwnerRecord())),
            ]);
    }
}
