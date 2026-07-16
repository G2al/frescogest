<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Models\Order;
use Filament\Actions\DeleteAction;

class OrderDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Elimina')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (Order $record): bool => ! $record->deliveryDocument()->exists())
            ->requiresConfirmation()
            ->modalHeading('Eliminare definitivamente l’ordine?')
            ->modalDescription('L’ordine e tutte le righe prodotto collegate verranno eliminati definitivamente. Questa operazione non può essere annullata.')
            ->modalSubmitActionLabel('Elimina definitivamente')
            ->successNotificationTitle('Ordine eliminato definitivamente');
    }
}
