<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Models\Order;
use App\Services\Orders\DeleteOrderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class OrderDeleteAction
{
    public static function make(): Action
    {
        return Action::make('deleteOrder')
            ->label('Elimina')
            ->icon('heroicon-o-trash')
            ->iconButton()
            ->tooltip('Elimina definitivamente')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Eliminare definitivamente l’ordine?')
            ->modalDescription('L’ordine, le righe prodotto e l’eventuale DDT collegato verranno eliminati definitivamente. Questa operazione non può essere annullata.')
            ->modalSubmitActionLabel('Elimina definitivamente')
            ->action(function (Order $record): void {
                app(DeleteOrderService::class)->delete($record);

                Notification::make()
                    ->success()
                    ->title('Ordine eliminato definitivamente')
                    ->send();
            });
    }
}
