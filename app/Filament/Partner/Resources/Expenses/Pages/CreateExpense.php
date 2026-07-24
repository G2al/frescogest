<?php

namespace App\Filament\Partner\Resources\Expenses\Pages;

use App\Filament\Partner\Resources\Expenses\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['partner_id'] = auth('admin')->user()->partner->getKey();

        return $data;
    }
}
