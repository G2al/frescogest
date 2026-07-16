<?php

namespace App\Filament\Resources\TaxRates;

use App\Filament\Resources\TaxRates\Pages\CreateTaxRate;
use App\Filament\Resources\TaxRates\Pages\EditTaxRate;
use App\Filament\Resources\TaxRates\Pages\ListTaxRates;
use App\Filament\Resources\TaxRates\Schemas\TaxRateForm;
use App\Filament\Resources\TaxRates\Tables\TaxRatesTable;
use App\Models\TaxRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Configurazione';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'aliquota IVA';

    protected static ?string $pluralModelLabel = 'aliquote IVA';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TaxRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxRatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxRates::route('/'),
            'create' => CreateTaxRate::route('/create'),
            'edit' => EditTaxRate::route('/{record}/edit'),
        ];
    }
}
