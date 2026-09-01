<?php

namespace App\Filament\Resources\SpecialPriceRules\Pages;

use App\Filament\Resources\SpecialPriceRules\SpecialPriceRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpecialPriceRules extends ListRecords
{
    protected static string $resource = SpecialPriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
