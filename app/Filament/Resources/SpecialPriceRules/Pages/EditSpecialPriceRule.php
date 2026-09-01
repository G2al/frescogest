<?php

namespace App\Filament\Resources\SpecialPriceRules\Pages;

use App\Filament\Resources\SpecialPriceRules\SpecialPriceRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpecialPriceRule extends EditRecord
{
    protected static string $resource = SpecialPriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Elimina definitivamente')];
    }
}
