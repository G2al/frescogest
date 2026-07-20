<?php

namespace App\Filament\Resources\CostCategories;

use App\Filament\Resources\CostCategories\Pages\CreateCostCategory;
use App\Filament\Resources\CostCategories\Pages\EditCostCategory;
use App\Filament\Resources\CostCategories\Pages\ListCostCategories;
use App\Filament\Resources\CostCategories\RelationManagers\MovementsRelationManager;
use App\Models\CostCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CostCategoryResource extends Resource
{
    protected static ?string $model = CostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Contabilità';

    protected static ?string $modelLabel = 'categoria di costo';

    protected static ?string $pluralModelLabel = 'costi extra';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Categoria')->columnSpanFull()->schema([
                TextInput::make('name')->label('Nome')->required()->unique(ignoreRecord: true),
                Toggle::make('is_monthly')->label('Movimento mensile'),
                Toggle::make('active')->label('Attiva')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Categoria')->searchable()->sortable(),
            TextColumn::make('movements_sum_amount')->label('Totale')->sum('movements', 'amount')->money('EUR'),
            TextColumn::make('is_monthly')->label('Frequenza')->formatStateUsing(fn (bool $state) => $state ? 'Mensile' : 'Giornaliera')->badge(),
            TextColumn::make('active')->label('Stato')->formatStateUsing(fn (bool $state) => $state ? 'Attiva' : 'Disattivata')->badge()->color(fn (bool $state) => $state ? 'success' : 'gray'),
        ])->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [MovementsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListCostCategories::route('/'), 'create' => CreateCostCategory::route('/create'), 'edit' => EditCostCategory::route('/{record}/edit')];
    }
}
