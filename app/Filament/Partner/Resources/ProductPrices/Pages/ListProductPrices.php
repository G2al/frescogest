<?php

namespace App\Filament\Partner\Resources\ProductPrices\Pages;

use App\Filament\Partner\Resources\ProductPrices\ProductPriceResource;
use Filament\Resources\Pages\ListRecords;

class ListProductPrices extends ListRecords
{
    protected static string $resource = ProductPriceResource::class;
}
