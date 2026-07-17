<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Models\Company;
use App\Models\Order;
use App\Services\Documents\CreateDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class DeliveryDocumentActions
{
    public static function make(): array
    {
        return [
            Action::make('generateDeliveryDocument')
                ->label('Genera DDT')
                ->icon('heroicon-o-document-text')
                ->iconButton()
                ->tooltip('Genera DDT')
                ->color('primary')
                ->visible(fn (Order $record): bool => filled($record->paid_at) && ! $record->deliveryDocument()->exists())
                ->modalHeading('Genera documento di trasporto')
                ->modalDescription('I dati anagrafici e le righe dell’ordine verranno salvati come snapshot non modificabile.')
                ->schema([
                    Select::make('company_id')
                        ->label('Azienda emittente')
                        ->options(fn (): array => Company::query()
                            ->where('active', true)
                            ->orderBy('business_name')
                            ->pluck('business_name', 'id')
                            ->all())
                        ->searchable()
                        ->helperText('Se non compare alcuna azienda, creane o attivane una dalla sezione Aziende.')
                        ->required(),
                    DateTimePicker::make('issued_at')
                        ->label('Data e ora emissione')
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('transport_started_at')
                        ->label('Inizio trasporto')
                        ->seconds(false),
                    Select::make('transport_reason')
                        ->label('Causale del trasporto')
                        ->options([
                            'Vendita' => 'Vendita',
                            'Conto visione' => 'Conto visione',
                            'Conto lavorazione' => 'Conto lavorazione',
                            'Reso' => 'Reso',
                            'Omaggio' => 'Omaggio',
                            'Trasferimento interno' => 'Trasferimento interno',
                            'Altro' => 'Altro',
                        ])
                        ->required(),
                    Select::make('transport_method')
                        ->label('Trasporto a cura di')
                        ->options([
                            'Mittente' => 'Mittente',
                            'Destinatario' => 'Destinatario',
                            'Vettore' => 'Vettore',
                        ])
                        ->required(),
                    TextInput::make('goods_appearance')
                        ->label('Aspetto esteriore dei beni')
                        ->maxLength(255),
                    TextInput::make('packages_count')
                        ->label('Numero colli')
                        ->numeric()
                        ->integer()
                        ->minValue(1),
                    TextInput::make('total_weight')
                        ->label('Peso totale (kg)')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('carrier_name')
                        ->label('Vettore')
                        ->maxLength(255),
                    TextInput::make('carrier_vat_number')
                        ->label('Partita IVA vettore')
                        ->maxLength(32),
                    TextInput::make('carrier_tax_code')
                        ->label('Codice fiscale vettore')
                        ->maxLength(32),
                    TextInput::make('vehicle_registration')
                        ->label('Targa mezzo')
                        ->maxLength(20),
                    Textarea::make('notes')
                        ->label('Annotazioni')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->fillForm(fn (Order $record): array => [
                    'company_id' => Company::query()
                        ->where('active', true)
                        ->oldest('id')
                        ->value('id'),
                    'issued_at' => now(),
                    'transport_started_at' => $record->delivered_at ?? $record->expected_delivery_at,
                    'transport_reason' => 'Vendita',
                    'transport_method' => 'Mittente',
                    'goods_appearance' => 'Colli',
                ])
                ->action(function (Order $record, array $data): void {
                    try {
                        app(CreateDeliveryDocumentService::class)->create($record, auth()->user(), $data);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('DDT non generato')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Controlla i dati inseriti.')
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('DDT generato correttamente')
                        ->body('Il documento è ora disponibile per il download.')
                        ->send();
                }),
            Action::make('downloadDeliveryDocument')
                ->label('Scarica DDT')
                ->icon('heroicon-o-arrow-down-tray')
                ->iconButton()
                ->tooltip('Scarica DDT')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->deliveryDocument()->exists())
                ->url(fn (Order $record): string => route('admin.orders.delivery-document', $record))
                ->openUrlInNewTab(),
        ];
    }
}
