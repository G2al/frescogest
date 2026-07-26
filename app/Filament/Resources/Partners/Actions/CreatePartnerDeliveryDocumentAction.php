<?php

namespace App\Filament\Resources\Partners\Actions;

use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\Documents\CreatePartnerDeliveryDocumentService;
use App\Services\Partners\PartnerDeliveryDocumentPricingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;

class CreatePartnerDeliveryDocumentAction
{
    public static function make(Partner $partner): Action
    {
        return Action::make('createPartnerDeliveryDocument')
            ->label('Nuova bolla per Angela')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->modalHeading("Crea bolla per {$partner->name}")
            ->modalDescription('La merce inserita verrà registrata automaticamente anche nei carichi del partner.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Genera bolla')
            ->schema([
                Section::make('Documento')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('issued_at')
                            ->label('Data e ora emissione')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Select::make('payment_method_snapshot')
                            ->label('Metodo di pagamento')
                            ->options(fn (): array => PaymentMethod::query()
                                ->where('active', true)
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->placeholder('Da concordare'),
                    ]),
                Section::make('Prodotti consegnati')
                    ->description('I prezzi proposti sono quelli che Angela paga ad Antonio e possono essere corretti per questa bolla.')
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
                                    ->afterStateUpdated(function ($state, Set $set) use ($partner): void {
                                        $details = app(PartnerDeliveryDocumentPricingService::class)
                                            ->product($partner, $state);

                                        $set('unit_price_net', $details['price'] ?? null);
                                        $set('quantity', 1);
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
                                Hidden::make('unit_symbol'),
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
                    ->label('Note')
                    ->rows(3),
            ])
            ->action(function (array $data, Component $livewire) use ($partner): void {
                $document = app(CreatePartnerDeliveryDocumentService::class)
                    ->create($partner, auth()->user(), $data);

                Notification::make()
                    ->success()
                    ->title('Bolla generata')
                    ->body("La {$document->document_number} è stata aggiunta anche alla merce caricata di {$partner->name}.")
                    ->send();

                $url = route('admin.delivery-documents.show', $document);
                $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
            });
    }

    private static function lineGross(Get $get): string
    {
        $totals = app(PartnerDeliveryDocumentPricingService::class)->totals([[
            'product_id' => $get('product_id'),
            'quantity' => $get('quantity'),
            'unit_price_net' => $get('unit_price_net'),
        ]]);

        return self::currency($totals['gross']);
    }

    private static function totals(array $items): string
    {
        $totals = app(PartnerDeliveryDocumentPricingService::class)->totals($items);

        return 'Netto: '.self::currency($totals['net'])
            .' · IVA: '.self::currency($totals['tax'])
            .' · Totale: '.self::currency($totals['gross']);
    }

    private static function currency(string|int|float $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' €';
    }
}
