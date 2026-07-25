<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Orders\CreateManualOrderService;
use App\Services\Orders\ManualOrderPricingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CreateManualOrderAction
{
    public static function make(): Action
    {
        return Action::make('createManualOrder')
            ->label('Nuovo ordine manuale')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Crea ordine manuale')
            ->modalDescription('Inserisci cliente, prodotti e prezzi. L’ordine verrà salvato nella gestione ordinaria.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Crea ordine')
            ->schema([
                Section::make('Cliente e stato')
                    ->columns(2)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(fn (): array => Customer::query()
                                ->where('active', true)
                                ->orderBy('company_name')
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    $customer->getKey() => $customer->display_name,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $customer = Customer::query()->find($state);

                                $set('delivery_address', $customer?->delivery_address);
                                $set('delivery_city', $customer?->city);
                                $set('delivery_postal_code', $customer?->postal_code);
                                $set('delivery_province', $customer?->province);
                                $set('items', []);
                            })
                            ->required(),
                        Select::make('status')
                            ->label('Stato iniziale')
                            ->options([
                                OrderStatus::Confirmed->value => OrderStatus::Confirmed->label(),
                                OrderStatus::WhatsAppPending->value => OrderStatus::WhatsAppPending->label(),
                            ])
                            ->default(OrderStatus::Confirmed->value)
                            ->required(),
                        DateTimePicker::make('requested_at')
                            ->label('Data ordine')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('expected_delivery_at')
                            ->label('Consegna prevista')
                            ->seconds(false),
                    ]),
                Section::make('Prodotti')
                    ->description('Il prezzo proposto rispetta il listino del cliente, ma può essere corretto per questo ordine.')
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
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        $details = app(ManualOrderPricingService::class)->product(
                                            $state,
                                            $get('../../customer_id'),
                                        );

                                        $set('unit_price_net', $details['price'] ?? null);
                                        $set('quantity', $details['minimum_quantity'] ?? 1);
                                        $set('minimum_quantity', $details['minimum_quantity'] ?? null);
                                        $set('unit_symbol', $details['unit_symbol'] ?? null);
                                    })
                                    ->columnSpan(5)
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Quantità')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->live(debounce: 300)
                                    ->suffix(fn (Get $get): ?string => $get('unit_symbol'))
                                    ->helperText(fn (Get $get): ?string => filled($get('minimum_quantity'))
                                        ? 'Listino: minimo '.$get('minimum_quantity').' '.$get('unit_symbol')
                                        : null)
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
                        Placeholder::make('order_totals')
                            ->hiddenLabel()
                            ->content(fn (Get $get): string => self::totals($get('items') ?? [])),
                    ]),
                Section::make('Consegna')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('delivery_address')->label('Indirizzo'),
                        TextInput::make('delivery_city')->label('Città'),
                        TextInput::make('delivery_postal_code')->label('CAP')->maxLength(10),
                        TextInput::make('delivery_province')
                            ->label('Provincia')
                            ->maxLength(2)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        Textarea::make('delivery_notes')->label('Note consegna')->columnSpanFull(),
                    ]),
                Section::make('Note')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Textarea::make('customer_notes')->label('Note cliente'),
                        Textarea::make('internal_notes')->label('Note interne'),
                    ]),
            ])
            ->action(function (array $data) {
                $order = app(CreateManualOrderService::class)->create($data);

                Notification::make()
                    ->success()
                    ->title('Ordine creato')
                    ->body("L’ordine {$order->order_number} è pronto per essere gestito.")
                    ->send();

                return redirect(OrderResource::getUrl('edit', ['record' => $order]));
            });
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
