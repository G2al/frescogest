<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Services\Pricing\ProductListPriceCalculator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
        return $schema->components([
            Section::make('Articolo')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug((string) $state)))
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Codice articolo')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('brand')
                        ->label('Marca')
                        ->maxLength(255),
                    Select::make('product_category_id')
                        ->label('Categoria')
                        ->relationship('productCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Toggle::make('active')
                        ->label('Disponibile')
                        ->default(true),
                    Hidden::make('slug'),
                    Hidden::make('tax_rate_id')
                        ->default(fn () => TaxRate::query()->where('percentage', 0)->value('id')),
                    Hidden::make('default_unit_of_measure_id')
                        ->default(fn () => UnitOfMeasure::query()->where('symbol', 'pz')->value('id')),
                ])
                ->columns(2),
            Section::make('Prezzi')
                ->description('I prezzi sono riferiti al singolo capo e sono mostrati come importi finali.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('purchase_cost_per_unit_gross')
                        ->label('Costo di acquisto')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('€')
                        ->required()
                        ->live(debounce: 400)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncPricing($get, $set)),
                    TextInput::make('base_price_per_unit')
                        ->label('Prezzo di vendita')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('€')
                        ->required()
                        ->live(debounce: 400)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncPricing($get, $set)),
                    Hidden::make('purchase_cost_per_unit'),
                    Hidden::make('markup_percentage'),
                    Hidden::make('restaurant_markup_percentage'),
                    Hidden::make('restaurant_price_per_unit'),
                    Hidden::make('price_per_kg'),
                    Hidden::make('base_minimum_quantity')->default(1),
                    Hidden::make('restaurant_minimum_quantity')->default(1),
                    Hidden::make('is_public')->default(true),
                    Hidden::make('is_seasonal')->default(false),
                    Hidden::make('sort_order')->default(0),
                ])
                ->columns(2),
            Section::make('Presentazione')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Note interne')
                        ->rows(3)
                        ->columnSpanFull(),
                    FileUpload::make('image_path')
                        ->label('Immagine')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('catalog/products')
                        ->previewable()
                        ->openable()
                        ->imagePreviewHeight('280')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function syncPricing(Get $get, Set $set): void
    {
        $purchaseCost = (float) $get('purchase_cost_per_unit_gross');
        $sellingPrice = (float) $get('base_price_per_unit');
        $markup = app(ProductListPriceCalculator::class)->markupFromPrice($purchaseCost, $sellingPrice);

        $set('purchase_cost_per_unit', number_format($purchaseCost, 4, '.', ''));
        $set('markup_percentage', number_format($markup, 2, '.', ''));
        $set('restaurant_markup_percentage', number_format($markup, 2, '.', ''));
        $set('restaurant_price_per_unit', number_format($sellingPrice, 4, '.', ''));
        $set('price_per_kg', number_format($sellingPrice, 2, '.', ''));
    }
}
