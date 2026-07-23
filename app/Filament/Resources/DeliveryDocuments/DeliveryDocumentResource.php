<?php

namespace App\Filament\Resources\DeliveryDocuments;

use App\Filament\Resources\DeliveryDocuments\Pages\ListDeliveryDocuments;
use App\Filament\Resources\DeliveryDocuments\Tables\DeliveryDocumentsTable;
use App\Models\DeliveryDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryDocumentResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = DeliveryDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ordini';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Bolle';

    protected static ?string $modelLabel = 'bolla';

    protected static ?string $pluralModelLabel = 'bolle';

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function table(Table $table): Table
    {
        return DeliveryDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListDeliveryDocuments::route('/')];
    }
}
