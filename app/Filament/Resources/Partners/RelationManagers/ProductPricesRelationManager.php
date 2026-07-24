<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use App\Services\Pricing\ProductListPriceCalculator;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'productPrices';

    protected static ?string $title = 'Listino Angela';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('purchase_price_net')
                ->label('Prezzo pagato ad Antonio')
                ->helperText('Prezzo netto per unità. L’IVA del prodotto viene aggiunta automaticamente.')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix('€')
                ->required()
                ->live(debounce: 400)
                ->afterStateUpdated(fn (Get $get, Set $set) => $this->setSalePrice($get, $set)),
            TextInput::make('markup_percentage')
                ->label('Ricarico Angela')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->suffix('%')
                ->required()
                ->live(debounce: 400)
                ->afterStateUpdated(fn (Get $get, Set $set) => $this->setSalePrice($get, $set)),
            TextInput::make('sale_price_net')
                ->label('Prezzo al cliente finale')
                ->helperText('Puoi scriverlo manualmente: il ricarico verrà ricalcolato.')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix('€')
                ->required()
                ->live(debounce: 400)
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $markup = app(ProductListPriceCalculator::class)->markupFromPrice(
                        $get('purchase_price_net'),
                        $get('sale_price_net'),
                    );
                    $set('markup_percentage', number_format($markup, 2, '.', ''));
                }),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product.taxRate', 'product.defaultUnitOfMeasure']))
            ->columns([
                TextColumn::make('product.name')->label('Prodotto')->searchable()->sortable(),
                TextColumn::make('product.productCategory.name')->label('Categoria')->sortable(),
                TextColumn::make('purchase_price_net')->label('Paga netto')->money('EUR')->sortable(),
                TextColumn::make('purchase_price_gross')->label('Paga IVA inclusa')->money('EUR'),
                TextColumn::make('sale_price_net')->label('Vende netto')->money('EUR')->sortable(),
                TextColumn::make('sale_price_gross')->label('Vende IVA inclusa')->money('EUR'),
                TextColumn::make('margin_net')->label('Guadagno netto')->money('EUR'),
                TextColumn::make('markup_percentage')->label('Ricarico %')->suffix('%')->sortable(),
                TextColumn::make('product.defaultUnitOfMeasure.symbol')->label('Unità'),
            ])
            ->filters([
                SelectFilter::make('product_category_id')
                    ->label('Categoria')
                    ->relationship('product.productCategory', 'name'),
            ])
            ->defaultSort('product.name')
            ->recordActions([
                EditAction::make()->label('Modifica')->iconButton(),
            ]);
    }

    private function setSalePrice(Get $get, Set $set): void
    {
        $price = app(ProductListPriceCalculator::class)->priceFromMarkup(
            $get('purchase_price_net'),
            $get('markup_percentage'),
        );
        $set('sale_price_net', number_format($price, 2, '.', ''));
    }
}
