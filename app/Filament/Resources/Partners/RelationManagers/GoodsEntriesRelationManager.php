<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoodsEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'goodsEntries';

    protected static ?string $title = 'Merce caricata da Antonio';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('delivered_on')->label('Data carico')->default(today())->required(),
            Select::make('product_id')
                ->label('Prodotto')
                ->relationship('product', 'name', modifyQueryUsing: fn ($query) => $query->where('active', true))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('quantity')->label('Quantità')->numeric()->minValue(0.001)->step(0.001)->required(),
            Textarea::make('notes')->label('Note')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivered_on')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('product.name')->label('Prodotto')->searchable()->sortable(),
                TextColumn::make('quantity')->label('Quantità')->numeric(3),
                TextColumn::make('product.defaultUnitOfMeasure.symbol')->label('Unità'),
                TextColumn::make('unit_purchase_price_net')->label('Prezzo netto')->money('EUR'),
                TextColumn::make('tax_percentage')->label('IVA')->suffix('%'),
                TextColumn::make('total_gross')->label('Totale IVA inclusa')->money('EUR')->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')->label('Prodotto')->relationship('product', 'name')->searchable()->preload(),
            ])
            ->defaultSort('delivered_on', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
