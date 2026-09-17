<?php

namespace App\Filament\Resources\DeliveryDocuments\Actions;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\Orders\ManualOrderPricingService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class OrderDeliveryDocumentActionSchema
{
    public static function make(Order $order): array
    {
        return [
            Section::make('Documento')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('issued_at')
                        ->label('Data e ora emissione')
                        ->default(now())
                        ->seconds(false)
                        ->required(),
                    Select::make('payment_method_id')
                        ->label('Metodo di pagamento')
                        ->options(fn (): array => PaymentMethod::query()
                            ->where('active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->placeholder('Da concordare'),
                    TextInput::make('discount_percentage')
                        ->label('Sconto sull’ordine')
                        ->helperText('Lo sconto verrà applicato a tutte le righe, alla bolla e all’ordine collegato.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(0)
                        ->live(debounce: 350)
                        ->required(),
                ]),
            Section::make('Prodotti consegnati')
                ->description('Il prezzo proposto rispetta il listino del cliente, ma può essere corretto per questa bolla.')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('product_id')
                                ->label('Prodotto')
                                ->options(fn (): array => Product::query()
                                    ->where('active', true)
                                    ->with('productCategory')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Product $product): array => [
                                        $product->getKey() => $product->name.' · '.$product->productCategory->name,
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) use ($order): void {
                                    $details = app(ManualOrderPricingService::class)->product($state, $order->customer_id);

                                    $set('unit_price_net', $details['price'] ?? null);
                                    $set('quantity', $details['minimum_quantity'] ?? 1);
                                    $set('unit_symbol', $details['unit_symbol'] ?? null);
                                })
                                ->columnSpan(5)
                                ->required(),
                            TextInput::make('quantity')
                                ->label('Quantità')
                                ->numeric()
                                ->minValue(0.001)
                                ->step(0.001)
                                ->suffix(fn (Get $get): ?string => $get('unit_symbol'))
                                ->live(debounce: 300)
                                ->columnSpan(2)
                                ->required(),
                            TextInput::make('unit_price_net')
                                ->label('Prezzo unitario netto')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix('€')
                                ->live(debounce: 300)
                                ->columnSpan(2)
                                ->required(),
                            Placeholder::make('line_total')
                                ->label('Totale IVA inclusa')
                                ->content(fn (Get $get): string => self::lineGross($get))
                                ->columnSpan(3),
                        ])
                        ->columns(12)
                        ->itemLabel(fn (array $state): ?string => Product::query()
                            ->whereKey($state['product_id'] ?? null)
                            ->value('name'))
                        ->addActionLabel('Aggiungi prodotto')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->reorderable(false)
                        ->required(),
                    Placeholder::make('document_totals')
                        ->hiddenLabel()
                        ->content(fn (Get $get): string => self::totals($get('items') ?? [])),
                ]),
            Textarea::make('notes')
                ->label('Note interne')
                ->rows(3),
        ];
    }

    private static function lineGross(Get $get): string
    {
        $totals = app(ManualOrderPricingService::class)->totals([[
            'product_id' => $get('product_id'),
            'quantity' => $get('quantity'),
            'unit_price_net' => $get('unit_price_net'),
        ]]);

        return self::currency($totals['gross']);
    }

    private static function totals(array $items): string
    {
        $totals = app(ManualOrderPricingService::class)->totals($items);

        return 'Netto: '.self::currency($totals['net'])
            .' · IVA: '.self::currency($totals['tax'])
            .' · Totale: '.self::currency($totals['gross']);
    }

    private static function currency(string|int|float $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' €';
    }
}
