<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\CustomerType;
use App\Filament\Resources\Orders\Actions\CreateManualOrderAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateManualOrderAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tutti'),
            'private' => Tab::make('Privati')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas(
                    'customer',
                    fn (Builder $customers): Builder => $customers->where('type', CustomerType::Private->value),
                )),
            'restaurant' => Tab::make('Ristoratori')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas(
                    'customer',
                    fn (Builder $customers): Builder => $customers->where('type', CustomerType::Restaurant->value),
                )),
        ];
    }
}
