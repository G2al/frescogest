<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Services\Customers\DeleteCustomerService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Elimina definitivamente')
                ->modalHeading('Eliminare definitivamente il cliente?')
                ->modalDescription('Verranno eliminati anche l’account, gli ordini e tutti i dati collegati. L’operazione non può essere annullata.')
                ->modalSubmitActionLabel('Elimina definitivamente')
                ->using(fn (Customer $record) => app(DeleteCustomerService::class)->delete($record)),
        ];
    }
}
