<?php

namespace App\Filament\Resources\Customers\Actions;

use App\Services\Customers\DeleteCustomerService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class CustomerBulkActions
{
    public static function delete(): BulkAction
    {
        return BulkAction::make('deleteCustomers')
            ->label('Elimina definitivamente')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Eliminare definitivamente i clienti selezionati?')
            ->modalDescription('Verranno eliminati anche gli account, gli ordini e tutti i dati collegati. Questa operazione non può essere annullata.')
            ->modalSubmitActionLabel('Elimina definitivamente')
            ->action(function (Collection $records): void {
                app(DeleteCustomerService::class)->deleteMany($records);

                Notification::make()
                    ->success()
                    ->title('Clienti eliminati definitivamente')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
