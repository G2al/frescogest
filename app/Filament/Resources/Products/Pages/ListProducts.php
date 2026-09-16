<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCatalogPdf')
                ->label('Scarica PDF catalogo')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(route('admin.products.catalog-pdf'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
