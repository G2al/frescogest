<?php

namespace App\Filament\Resources\PromotionCodes;

use App\Filament\Resources\PromotionCodes\Pages\CreatePromotionCode;
use App\Filament\Resources\PromotionCodes\Pages\EditPromotionCode;
use App\Filament\Resources\PromotionCodes\Pages\ListPromotionCodes;
use App\Filament\Resources\PromotionCodes\Schemas\PromotionCodeForm;
use App\Filament\Resources\PromotionCodes\Tables\PromotionCodesTable;
use App\Models\PromotionCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PromotionCodeResource extends Resource
{
    protected static ?string $model = PromotionCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Configurazione';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Scontistica';

    protected static ?string $modelLabel = 'codice sconto';

    protected static ?string $pluralModelLabel = 'scontistica';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PromotionCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromotionCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotionCodes::route('/'),
            'create' => CreatePromotionCode::route('/create'),
            'edit' => EditPromotionCode::route('/{record}/edit'),
        ];
    }
}
