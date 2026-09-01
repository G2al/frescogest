<?php

namespace App\Filament\Resources\SpecialPriceRules;

use App\Filament\Resources\SpecialPriceRules\Pages\CreateSpecialPriceRule;
use App\Filament\Resources\SpecialPriceRules\Pages\EditSpecialPriceRule;
use App\Filament\Resources\SpecialPriceRules\Pages\ListSpecialPriceRules;
use App\Filament\Resources\SpecialPriceRules\Schemas\SpecialPriceRuleForm;
use App\Filament\Resources\SpecialPriceRules\Tables\SpecialPriceRulesTable;
use App\Filament\Support\AdminOnlyResource;
use App\Models\SpecialPriceRule;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SpecialPriceRuleResource extends AdminOnlyResource
{
    protected static ?string $model = SpecialPriceRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Configurazione';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Prezzi speciali';

    protected static ?string $modelLabel = 'prezzo speciale';

    protected static ?string $pluralModelLabel = 'prezzi speciali';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SpecialPriceRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpecialPriceRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialPriceRules::route('/'),
            'create' => CreateSpecialPriceRule::route('/create'),
            'edit' => EditSpecialPriceRule::route('/{record}/edit'),
        ];
    }
}
