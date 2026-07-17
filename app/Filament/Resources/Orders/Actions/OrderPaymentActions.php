<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class OrderPaymentActions
{
    public static function make(): array
    {
        return [
            Action::make('markPaid')
                ->label('Segna pagato')
                ->icon('heroicon-o-banknotes')
                ->iconButton()
                ->tooltip('Segna come pagato')
                ->color('success')
                ->visible(fn (Order $record): bool => blank($record->paid_at) && in_array($record->status, [
                    OrderStatus::Confirmed,
                    OrderStatus::Preparing,
                    OrderStatus::Delivered,
                ], true))
                ->schema([
                    DateTimePicker::make('paid_at')
                        ->label('Pagato il')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('payment_reference')
                        ->label('Riferimento pagamento')
                        ->maxLength(255),
                ])
                ->fillForm(fn (): array => ['paid_at' => now()])
                ->action(function (Order $record, array $data): void {
                    $record->update($data);

                    Notification::make()
                        ->success()
                        ->title('Pagamento registrato')
                        ->send();
                }),
            Action::make('markUnpaid')
                ->label('Annulla pagamento')
                ->icon('heroicon-o-arrow-uturn-left')
                ->iconButton()
                ->tooltip('Annulla pagamento')
                ->color('warning')
                ->visible(fn (Order $record): bool => filled($record->paid_at) && ! $record->deliveryDocument()->exists())
                ->requiresConfirmation()
                ->modalHeading('Annullare la registrazione del pagamento?')
                ->action(function (Order $record): void {
                    $record->update([
                        'paid_at' => null,
                        'payment_reference' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Pagamento annullato')
                        ->send();
                }),
        ];
    }
}
