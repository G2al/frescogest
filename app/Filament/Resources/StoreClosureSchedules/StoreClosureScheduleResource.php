<?php

namespace App\Filament\Resources\StoreClosureSchedules;

use App\Filament\Resources\StoreClosureSchedules\Pages\CreateStoreClosureSchedule;
use App\Filament\Resources\StoreClosureSchedules\Pages\EditStoreClosureSchedule;
use App\Filament\Resources\StoreClosureSchedules\Pages\ListStoreClosureSchedules;
use App\Filament\Resources\StoreClosureSchedules\Schemas\StoreClosureScheduleForm;
use App\Filament\Resources\StoreClosureSchedules\Tables\StoreClosureSchedulesTable;
use App\Models\StoreClosureSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StoreClosureScheduleResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = StoreClosureSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Configurazione';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Orari di chiusura';

    protected static ?string $modelLabel = 'orario di chiusura';

    protected static ?string $pluralModelLabel = 'orari di chiusura';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StoreClosureScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreClosureSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreClosureSchedules::route('/'),
            'create' => CreateStoreClosureSchedule::route('/create'),
            'edit' => EditStoreClosureSchedule::route('/{record}/edit'),
        ];
    }
}
