<?php

namespace App\Filament\Resources\CommercialRules;

use App\Filament\Resources\CommercialRules\Pages\CreateCommercialRule;
use App\Filament\Resources\CommercialRules\Pages\EditCommercialRule;
use App\Filament\Resources\CommercialRules\Pages\ListCommercialRules;
use App\Filament\Resources\CommercialRules\Schemas\CommercialRuleForm;
use App\Filament\Resources\CommercialRules\Tables\CommercialRulesTable;
use App\Models\CommercialRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CommercialRuleResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = CommercialRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Configurazione';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'regola commerciale';

    protected static ?string $pluralModelLabel = 'regole commerciali';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CommercialRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommercialRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommercialRules::route('/'),
            'create' => CreateCommercialRule::route('/create'),
            'edit' => EditCommercialRule::route('/{record}/edit'),
        ];
    }
}
