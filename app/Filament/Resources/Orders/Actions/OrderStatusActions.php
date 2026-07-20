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
                ->icon('heroicon-o-check-circle')->iconButton()->tooltip('Conferma ordine')->color('info')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::WhatsAppPending)
                ->requiresConfirmation()
                ->action(fn (Order $record) => self::update($record, OrderStatus::Confirmed, 'Ordine confermato')),
            Action::make('cancelOrder')
                ->icon('heroicon-o-x-circle')->iconButton()->tooltip('Annulla ordine')->color('danger')
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::WhatsAppPending, OrderStatus::Confirmed], true))
                ->requiresConfirmation()
                ->action(fn (Order $record) => self::update($record, OrderStatus::Cancelled, 'Ordine annullato')),
            Action::make('reopenOrder')
                ->icon('heroicon-o-arrow-uturn-left')->iconButton()->tooltip('Riporta in trattativa')->color('warning')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Cancelled)
                ->action(fn (Order $record) => self::update($record, OrderStatus::WhatsAppPending, 'Ordine riaperto')),
        ];
    }

    private static function update(Order $order, OrderStatus $status, string $message): void
    {
        app(UpdateOrderStatusService::class)->update($order, $status);
        Notification::make()->success()->title($message)->send();
    }
}
