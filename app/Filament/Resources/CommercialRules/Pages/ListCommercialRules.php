<?php

namespace App\Filament\Resources\CommercialRules\Pages;

use App\Filament\Resources\CommercialRules\CommercialRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialRules extends ListRecords
{
    protected static string $resource = CommercialRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
