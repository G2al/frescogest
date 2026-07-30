<?php

namespace App\Filament\Resources\DeliveryDocuments;

use App\Filament\Resources\DeliveryDocuments\Pages\ListDeliveryDocuments;
use App\Filament\Resources\DeliveryDocuments\Tables\DeliveryDocumentsTable;
use App\Models\DeliveryDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DeliveryDocumentResource extends Resource
{
    protected static ?string $model = DeliveryDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ordini';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Bolle';

    protected static ?string $modelLabel = 'bolla';

    protected static ?string $pluralModelLabel = 'bolle';

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function getNavigationLabel(): string
    {
        return auth('admin')->user()?->hasPartnerPanelRole()
            ? 'Le mie bolle'
            : 'Bolle';
    }

    public static function table(Table $table): Table
    {
        return DeliveryDocumentsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user?->hasAdminPanelRole() === true
            || $user?->hasPartnerPanelRole() === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true
            && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true
            && parent::canDelete($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('admin')->user();

        if ($user?->hasPartnerPanelRole()) {
            $query->where('partner_id', $user->partner->getKey());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return ['index' => ListDeliveryDocuments::route('/')];
    }
}
