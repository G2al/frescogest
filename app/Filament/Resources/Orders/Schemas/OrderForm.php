<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ordine')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('order_number')->label('Numero ordine')->disabled(),
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'company_name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->searchable(['company_name', 'first_name', 'last_name'])
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Stato')
                            ->options(OrderStatus::options())
                            ->default(OrderStatus::PendingContact->value)
                            ->required(),
                        Select::make('payment_method_id')
                            ->label('Metodo di pagamento')
                            ->relationship('paymentMethod', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('requested_at')
                            ->label('Richiesto il')
                            ->default(now())
                            ->required(),
                        TextInput::make('total_amount')
                            ->label('Totale ordine')
                            ->prefix('€')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Consegna')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('delivery_address')->label('Indirizzo'),
                        TextInput::make('delivery_city')->label('Città'),
                        TextInput::make('delivery_postal_code')->label('CAP')->maxLength(10),
                        TextInput::make('delivery_province')->label('Provincia')->maxLength(2),
                        DateTimePicker::make('expected_delivery_at')->label('Consegna prevista'),
                        DateTimePicker::make('delivered_at')->label('Consegnato il'),
                        Textarea::make('delivery_notes')->label('Note consegna')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Pagamento e documento di trasporto')
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('paid_at')
                            ->label('Pagato il')
                            ->disabled(fn (?Order $record): bool => $record?->deliveryDocument()->exists() ?? false)
                            ->seconds(false),
                        TextInput::make('payment_reference')
                            ->label('Riferimento pagamento')
                            ->disabled(fn (?Order $record): bool => $record?->deliveryDocument()->exists() ?? false)
                            ->maxLength(255),
                        TextInput::make('deliveryDocument.document_number')
                            ->label('Numero DDT')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Note')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('customer_notes')->label('Note cliente')->rows(4),
                        Textarea::make('internal_notes')->label('Note interne')->rows(4),
                    ])
                    ->columns(2),
            ]);
    }
}
