<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\Actions\CreatePartnerDeliveryDocumentAction;
use App\Filament\Resources\Partners\PartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreatePartnerDeliveryDocumentAction::make($this->getRecord()),
            DeleteAction::make(),
        ];
    }
}
