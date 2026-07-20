<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Services\Pricing\ProductListPriceCalculator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati prodotto')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug((string) $state)))
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Codice')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attivo')
                            ->default(true),
                        TextInput::make('purchase_cost_per_unit')
                            ->label('Costo di acquisto netto')
                            ->helperText('Costo senza IVA per ogni kg, cassa, pezzo o altra unità selezionata.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->prefix('€')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateCalculatedPrices($get, $set)),
                        TextInput::make('markup_percentage')
                            ->label('Ricarico sul costo')
                            ->helperText('100% significa che il prezzo di vendita è il doppio del costo di acquisto.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(100)
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateCalculatedPrices($get, $set)),
                        TextInput::make('base_price_per_unit')
                            ->label('Prezzo listino base netto')
                            ->helperText('Calcolato automaticamente: costo di acquisto + ricarico.')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('base_minimum_quantity')
                            ->label('Quantità minima listino base')
                            ->helperText('Quantità minima acquistabile espressa nell’unità di misura selezionata.')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->required(),
                        TextInput::make('restaurant_price_per_unit')
                            ->label('Prezzo listino ristoratori netto')
                            ->helperText('Uguale al prezzo unitario del listino base; cambia soltanto la quantità minima.')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('restaurant_minimum_quantity')
                            ->label('Quantità minima ristoratori')
                            ->helperText('Quantità minima acquistabile da un ristoratore nell’unità selezionata.')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('public_description')
                            ->label('Descrizione pubblica')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Immagine pubblica')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('catalog/products')
                            ->previewable()
                            ->openable()
                            ->downloadable()
                            ->imagePreviewHeight('250')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Classificazione')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_category_id')
                            ->label('Categoria')
                            ->relationship('productCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tax_rate_id')
                            ->label('Aliquota IVA')
                            ->relationship('taxRate', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('default_unit_of_measure_id')
                            ->label('Unità di misura predefinita')
                            ->relationship('defaultUnitOfMeasure', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function updateCalculatedPrices(Get $get, Set $set): void
    {
        $prices = app(ProductListPriceCalculator::class)->calculate(
            $get('purchase_cost_per_unit'),
            $get('markup_percentage'),
        );

        $set('base_price_per_unit', number_format($prices['base_price'], 2, '.', ''));
        $set('restaurant_price_per_unit', number_format($prices['restaurant_price'], 2, '.', ''));
    }
}
