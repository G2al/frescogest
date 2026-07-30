<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use App\Services\Partners\PartnerAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PartnerAccountService::class)->create($data);
    }
}
