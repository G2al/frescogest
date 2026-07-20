<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\DeleteOrderService;
use App\Services\Orders\UpdateOrderStatusService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderBulkActions
{
    public static function make(): BulkActionGroup
    {
        return BulkActionGroup::make([
            BulkAction::make('confirmSelected')
                ->label('Conferma selezionati')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn (Collection $records) => self::updateStatus(
                    $records,
                    OrderStatus::WhatsAppPending,
                    OrderStatus::Confirmed,
                    'Ordini selezionati confermati',
                ))
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('cancelSelected')
                ->label('Annulla selezionati')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Collection $records): void {
                    DB::transaction(function () use ($records): void {
                        $records
                            ->filter(fn (Order $order): bool => in_array($order->status, [
                                OrderStatus::WhatsAppPending,
                                OrderStatus::Confirmed,
                            ], true))
                            ->each(fn (Order $order) => app(UpdateOrderStatusService::class)
                                ->update($order, OrderStatus::Cancelled));
                    });

                    self::notify('Ordini selezionati annullati');
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('deleteSelected')
                ->label('Elimina definitivamente')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Eliminare definitivamente gli ordini selezionati?')
                ->modalDescription('Verranno eliminati anche tutte le righe e gli eventuali DDT collegati.')
                ->action(function (Collection $records): void {
                    app(DeleteOrderService::class)->deleteMany($records);

                    self::notify('Ordini selezionati eliminati definitivamente');
                })
                ->deselectRecordsAfterCompletion(),
        ])->label('Azioni massive');
    }

    private static function updateStatus(
        Collection $records,
        OrderStatus $currentStatus,
        OrderStatus $newStatus,
        string $message,
    ): void {
        DB::transaction(function () use ($currentStatus, $newStatus, $records): void {
            $records
                ->filter(fn (Order $order): bool => $order->status === $currentStatus)
                ->each(fn (Order $order) => app(UpdateOrderStatusService::class)->update($order, $newStatus));
        });

        self::notify($message);
    }

    private static function notify(string $message): void
    {
        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}
