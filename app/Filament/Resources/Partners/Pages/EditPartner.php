<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\Actions\CreatePartnerDeliveryDocumentAction;
use App\Filament\Resources\Partners\PartnerResource;
use App\Services\Partners\PartnerAccountService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PartnerAccountService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreatePartnerDeliveryDocumentAction::make($this->getRecord()),
            DeleteAction::make(),
        ];
    }
}
