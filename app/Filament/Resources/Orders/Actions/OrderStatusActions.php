<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\UpdateOrderStatusService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class OrderStatusActions
{
    public static function make(): array
    {
        return [
            Action::make('confirmOrder')
                ->label('Conferma')
                ->icon('heroicon-o-check-circle')
                ->iconButton()
                ->tooltip('Conferma ordine')
                ->color('info')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::PendingContact)
                ->action(fn (Order $record) => self::update($record, OrderStatus::Confirmed, 'Ordine confermato')),
            Action::make('prepareOrder')
                ->label('Prepara')
                ->icon('heroicon-o-arrow-path')
                ->iconButton()
                ->tooltip('Avvia preparazione')
                ->color('primary')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed)
                ->action(fn (Order $record) => self::update($record, OrderStatus::Preparing, 'Ordine in preparazione')),
            Action::make('deliverOrder')
                ->label('Consegnato')
                ->icon('heroicon-o-truck')
                ->iconButton()
                ->tooltip('Segna come consegnato')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Preparing)
                ->requiresConfirmation()
                ->modalHeading('Confermare la consegna?')
                ->action(fn (Order $record) => self::update($record, OrderStatus::Delivered, 'Ordine consegnato')),
            Action::make('cancelOrder')
                ->label('Annulla')
                ->icon('heroicon-o-x-circle')
                ->iconButton()
                ->tooltip('Annulla ordine')
                ->color('danger')
                ->visible(fn (Order $record): bool => in_array($record->status, [
                    OrderStatus::PendingContact,
                    OrderStatus::Confirmed,
                    OrderStatus::Preparing,
                ], true))
                ->requiresConfirmation()
                ->modalHeading('Annullare questo ordine?')
                ->action(fn (Order $record) => self::update($record, OrderStatus::Cancelled, 'Ordine annullato')),
            Action::make('reopenOrder')
                ->label('Riattiva')
                ->icon('heroicon-o-arrow-uturn-left')
                ->iconButton()
                ->tooltip('Riattiva ordine')
                ->color('warning')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Cancelled)
                ->action(fn (Order $record) => self::update($record, OrderStatus::PendingContact, 'Ordine riattivato')),
            Action::make('returnToPreparation')
                ->label('Riapri')
                ->icon('heroicon-o-arrow-uturn-left')
                ->iconButton()
                ->tooltip('Riporta in preparazione')
                ->color('warning')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Delivered)
                ->requiresConfirmation()
                ->modalHeading('Riportare l’ordine in preparazione?')
                ->action(fn (Order $record) => self::update($record, OrderStatus::Preparing, 'Ordine riportato in preparazione')),
        ];
    }

    private static function update(Order $order, OrderStatus $status, string $message): void
    {
        app(UpdateOrderStatusService::class)->update($order, $status);

        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}
