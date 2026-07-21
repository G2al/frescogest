<?php

namespace App\Filament\Resources\CommercialRules\Pages;

use App\Filament\Resources\CommercialRules\CommercialRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommercialRule extends EditRecord
{
    protected static string $resource = CommercialRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
