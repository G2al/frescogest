<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\ApplyOrderDiscountService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeliveryDocumentActions
{
    public static function make(): array
    {
        return [
            Action::make('generateDeliveryDocument')
                ->icon('heroicon-o-document-text')->iconButton()->tooltip('Genera bolla di consegna')->color('primary')
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Confirmed, OrderStatus::Paid], true) && ! $record->deliveryDocument()->exists())
                ->modalHeading('Genera bolla di consegna')
                ->schema([
                    DateTimePicker::make('issued_at')->label('Data e ora emissione')->seconds(false)->required(),
                    TextInput::make('discount_percentage')->label('Sconto sull’ordine')->helperText('Lo sconto verrà registrato sulle righe, sulla bolla e nell’analisi economica.')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(0)->required()->live(debounce: 350)
                        ->afterStateUpdated(function ($state, Set $set, Order $record): void {
                            $total = app(ApplyOrderDiscountService::class)->estimateGross($record, $state);
                            $set('payment_amount', number_format($total, 2, '.', ''));
                        }),
                    Toggle::make('mark_as_paid')->label('Il cliente ha già pagato?')->live()->default(false),
                    Select::make('payment_method_id')->label('Metodo di pagamento')->options(fn () => PaymentMethod::query()->where('active', true)->pluck('name', 'id')->all())->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->required(fn (Get $get): bool => (bool) $get('mark_as_paid')),
                    TextInput::make('payment_amount')->label('Importo da pagare dopo lo sconto')->numeric()->prefix('€')->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->disabled()->dehydrated(false),
                    DateTimePicker::make('paid_at')->label('Pagato il')->seconds(false)->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->required(fn (Get $get): bool => (bool) $get('mark_as_paid')),
                ])
                ->fillForm(fn (Order $record): array => [
                    'issued_at' => now(),
                    'discount_percentage' => $record->discount_percentage,
                    'payment_method_id' => $record->payment_method_id,
                    'payment_amount' => $record->total_gross,
                    'paid_at' => $record->paid_at ?? now(),
                    'mark_as_paid' => $record->status === OrderStatus::Paid,
                ])
                ->action(function (Order $record, array $data, Component $livewire): void {
                    DB::transaction(function () use ($data, $record): void {
                        app(ApplyOrderDiscountService::class)->apply($record, $data['discount_percentage']);
                        $record->refresh();
                        $data['payment_amount'] = $record->total_gross;
                        app(CreateDeliveryDocumentService::class)->create($record, auth()->user(), $data);
                    });
                    Notification::make()->success()->title('Bolla di consegna generata')->send();
                    $url = route('admin.orders.delivery-document', $record);
                    $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
                }),
            Action::make('downloadDeliveryDocument')
                ->icon('heroicon-o-arrow-down-tray')->iconButton()->tooltip('Scarica bolla di consegna')->color('success')
                ->visible(fn (Order $record): bool => $record->deliveryDocument()->exists())
                ->url(fn (Order $record): string => route('admin.orders.delivery-document', $record))->openUrlInNewTab(),
        ];
    }
}
