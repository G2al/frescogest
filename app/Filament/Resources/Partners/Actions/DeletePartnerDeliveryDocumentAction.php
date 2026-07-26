<?php

namespace App\Filament\Resources\Partners\Actions;

use App\Models\DeliveryDocument;
use App\Services\Documents\PartnerDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DeletePartnerDeliveryDocumentAction
{
    public static function make(): Action
    {
        return Action::make('deletePartnerDeliveryDocument')
            ->label('Elimina')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->iconButton()
            ->tooltip('Elimina definitivamente bolla')
            ->visible(fn (DeliveryDocument $record): bool => filled($record->partner_id) && blank($record->order_id))
            ->requiresConfirmation()
            ->modalHeading('Eliminare definitivamente la bolla?')
            ->modalDescription('La bolla e i carichi partner collegati verranno eliminati definitivamente. Questa operazione non può essere annullata.')
            ->modalSubmitActionLabel('Elimina definitivamente')
            ->action(function (DeliveryDocument $record): void {
                $number = $record->document_number;

                app(PartnerDeliveryDocumentService::class)->delete($record);

                Notification::make()
                    ->success()
                    ->title('Bolla eliminata')
                    ->body("La {$number} e i relativi carichi sono stati eliminati.")
                    ->send();
            });
    }
}
