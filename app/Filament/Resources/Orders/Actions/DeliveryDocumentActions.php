<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Documents\CreateDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

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
                    Toggle::make('mark_as_paid')->label('Il cliente ha già pagato?')->live()->default(false),
                    Select::make('payment_method_id')->label('Metodo di pagamento')->options(fn () => PaymentMethod::query()->where('active', true)->pluck('name', 'id')->all())->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->required(fn (Get $get): bool => (bool) $get('mark_as_paid')),
                    TextInput::make('payment_amount')->label('Importo pagato')->numeric()->prefix('€')->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->required(fn (Get $get): bool => (bool) $get('mark_as_paid')),
                    DateTimePicker::make('paid_at')->label('Pagato il')->seconds(false)->visible(fn (Get $get): bool => (bool) $get('mark_as_paid'))->required(fn (Get $get): bool => (bool) $get('mark_as_paid')),
                ])
                ->fillForm(fn (Order $record): array => ['issued_at' => now(), 'payment_amount' => $record->total_gross, 'paid_at' => now(), 'mark_as_paid' => $record->status === OrderStatus::Paid])
                ->action(function (Order $record, array $data): void {
                    app(CreateDeliveryDocumentService::class)->create($record, auth()->user(), $data);
                    Notification::make()->success()->title('Bolla di consegna generata')->send();
                }),
            Action::make('downloadDeliveryDocument')
                ->icon('heroicon-o-arrow-down-tray')->iconButton()->tooltip('Scarica bolla di consegna')->color('success')
                ->visible(fn (Order $record): bool => $record->deliveryDocument()->exists())
                ->url(fn (Order $record): string => route('admin.orders.delivery-document', $record))->openUrlInNewTab(),
        ];
    }
}
