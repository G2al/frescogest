<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Orders\RecordOrderPaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class OrderPaymentActions
{
    public static function make(): array
    {
        return [
            Action::make('markPaid')
                ->icon('heroicon-o-banknotes')->iconButton()->tooltip('Registra pagamento')->color('success')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed)
                ->schema([
                    Select::make('payment_method_id')->label('Metodo')->options(fn () => PaymentMethod::query()->where('active', true)->pluck('name', 'id')->all())->required(),
                    TextInput::make('payment_amount')->label('Importo')->numeric()->prefix('€')->required(),
                    DateTimePicker::make('paid_at')->label('Data pagamento')->seconds(false)->required(),
                    TextInput::make('payment_reference')->label('Riferimento')->maxLength(255),
                ])
                ->fillForm(fn (Order $record): array => ['payment_amount' => $record->total_gross, 'paid_at' => now()])
                ->action(function (Order $record, array $data): void {
                    app(RecordOrderPaymentService::class)->record($record, $data);
                    Notification::make()->success()->title('Pagamento registrato')->send();
                }),
            Action::make('markUnpaid')
                ->icon('heroicon-o-arrow-uturn-left')->iconButton()->tooltip('Annulla pagamento')->color('warning')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid)
                ->requiresConfirmation()
                ->action(function (Order $record): void {
                    app(RecordOrderPaymentService::class)->clear($record);
                    Notification::make()->success()->title('Pagamento annullato')->send();
                }),
        ];
    }
}
