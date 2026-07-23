<?php

namespace App\Filament\Resources\Customers\Actions;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Services\Customers\DeleteCustomerService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CustomerDeleteAction
{
    public static function make(): Action
    {
        return Action::make('deleteCustomer')
            ->label('Elimina')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Eliminare definitivamente il cliente?')
            ->modalDescription('Verranno eliminati definitivamente anche l’account di accesso, tutti gli ordini, le righe prodotto e i documenti collegati. Questa operazione non può essere annullata.')
            ->modalSubmitActionLabel('Elimina definitivamente')
            ->successRedirectUrl(CustomerResource::getUrl('index'))
            ->action(function (Customer $record): void {
                app(DeleteCustomerService::class)->delete($record);

                Notification::make()
                    ->success()
                    ->title('Cliente eliminato definitivamente')
                    ->send();
            });
    }
}
