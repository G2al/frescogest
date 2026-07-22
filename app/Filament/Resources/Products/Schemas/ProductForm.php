<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\TaxRate;
use App\Services\Pricing\ProductListPriceCalculator;
use App\Services\Pricing\PurchaseCostCalculator;
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
        return $schema->components([
            Section::make('Dati prodotto')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->label('Nome')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug((string) $state)))->maxLength(255),
                    TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                    TextInput::make('code')->label('Codice')->maxLength(255),
                    Toggle::make('active')->label('Attivo')->default(true),
                    TextInput::make('purchase_cost_per_unit_gross')
                        ->label('Costo di acquisto IVA inclusa')
                        ->helperText('Inserisci il costo realmente pagato, già comprensivo di IVA, per ogni unità selezionata.')
                        ->numeric()->minValue(0)->step(0.0001)->prefix('€')->required()->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateNetCostAndPrices($get, $set)),
                    TextInput::make('purchase_cost_per_unit')
                        ->label('Costo di acquisto netto')
                        ->helperText('Calcolato automaticamente scorporando l’IVA dal costo pagato.')
                        ->numeric()->prefix('€')->disabled()->dehydrated(false),
                    Textarea::make('description')->label('Descrizione')->rows(3)->columnSpanFull(),
                    Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
                    Textarea::make('public_description')->label('Descrizione pubblica')->rows(3)->columnSpanFull(),
                    FileUpload::make('image_path')->label('Immagine pubblica')->image()->disk('public')->visibility('public')
                        ->directory('catalog/products')->previewable()->openable()->downloadable()->imagePreviewHeight('250')->columnSpanFull(),
                ])->columns(2),
            Section::make('Listino privati')
                ->description('Prezzo unitario e quantità minima applicati ai clienti privati.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('markup_percentage')
                        ->label('Ricarico privati sul costo')
                        ->helperText('Modificando il ricarico viene aggiornato automaticamente il prezzo privati.')
                        ->numeric()->minValue(0)->maxValue(10000)->step(0.01)->suffix('%')->default(100)->required()->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updatePriceFromMarkup($get, $set, 'markup_percentage', 'base_price_per_unit')),
                    TextInput::make('base_price_per_unit')
                        ->label('Prezzo privati netto')
                        ->helperText('Puoi inserirlo manualmente: il ricarico verrà ricalcolato automaticamente.')
                        ->numeric()->minValue(0)->step(0.01)->prefix('€')->required()->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateMarkupFromPrice($get, $set, 'base_price_per_unit', 'markup_percentage')),
                    TextInput::make('base_minimum_quantity')
                        ->label('Quantità minima privati')
                        ->helperText('Quantità minima acquistabile espressa nell’unità di misura selezionata.')
                        ->numeric()->minValue(0.001)->step(0.001)->required(),
                ])->columns(3),
            Section::make('Listino ristoratori')
                ->description('Prezzo unitario e quantità minima applicati ai clienti ristoratori.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('restaurant_markup_percentage')
                        ->label('Ricarico ristoratori sul costo')
                        ->helperText('È indipendente dal ricarico privati e aggiorna solo il prezzo ristoratori.')
                        ->numeric()->minValue(0)->maxValue(10000)->step(0.01)->suffix('%')->default(100)->required()->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updatePriceFromMarkup($get, $set, 'restaurant_markup_percentage', 'restaurant_price_per_unit')),
                    TextInput::make('restaurant_price_per_unit')
                        ->label('Prezzo ristoratori netto')
                        ->helperText('Puoi inserirlo manualmente senza vincoli rispetto al prezzo privati.')
                        ->numeric()->minValue(0)->step(0.01)->prefix('€')->required()->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateMarkupFromPrice($get, $set, 'restaurant_price_per_unit', 'restaurant_markup_percentage')),
                    TextInput::make('restaurant_minimum_quantity')
                        ->label('Quantità minima ristoratori')
                        ->helperText('Quantità minima acquistabile da un ristoratore nell’unità selezionata.')
                        ->numeric()->minValue(0.001)->step(0.001)->required(),
                ])->columns(3),
            Section::make('Classificazione')
                ->columnSpanFull()
                ->schema([
                    Select::make('product_category_id')->label('Categoria')->relationship('productCategory', 'name')->searchable()->preload()->required(),
                    Select::make('tax_rate_id')->label('Aliquota IVA')->relationship('taxRate', 'name')->searchable()->preload()->required()->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateNetCostAndPrices($get, $set)),
                    Select::make('default_unit_of_measure_id')->label('Unità di misura predefinita')->relationship('defaultUnitOfMeasure', 'name')->searchable()->preload()->required(),
                ])->columns(2),
        ]);
    }

    private static function updatePricesFromMarkups(Get $get, Set $set, string|int|float|null $purchaseCost = null): void
    {
        self::updatePriceFromMarkup($get, $set, 'markup_percentage', 'base_price_per_unit', $purchaseCost);
        self::updatePriceFromMarkup($get, $set, 'restaurant_markup_percentage', 'restaurant_price_per_unit', $purchaseCost);
    }

    private static function updateNetCostAndPrices(Get $get, Set $set): void
    {
        $percentage = TaxRate::query()->whereKey($get('tax_rate_id'))->value('percentage') ?? 0;
        $netCost = app(PurchaseCostCalculator::class)->netFromGross(
            $get('purchase_cost_per_unit_gross'),
            $percentage,
        );

        $set('purchase_cost_per_unit', number_format($netCost, 4, '.', ''));
        self::updatePricesFromMarkups($get, $set, $netCost);
    }

    private static function updatePriceFromMarkup(
        Get $get,
        Set $set,
        string $markupField,
        string $priceField,
        string|int|float|null $purchaseCost = null,
    ): void {
        $price = app(ProductListPriceCalculator::class)->priceFromMarkup(
            $purchaseCost ?? $get('purchase_cost_per_unit'),
            $get($markupField),
        );
        $set($priceField, number_format($price, 2, '.', ''));
    }

    private static function updateMarkupFromPrice(Get $get, Set $set, string $priceField, string $markupField): void
    {
        $markup = app(ProductListPriceCalculator::class)->markupFromPrice($get('purchase_cost_per_unit'), $get($priceField));
        $set($markupField, number_format($markup, 2, '.', ''));
    }
}
