<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\RelationManagers\DailyReceiptsRelationManager;
use App\Filament\Resources\Partners\RelationManagers\DailyWastesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\GoodsEntriesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\ProductPricesRelationManager;
use App\Models\Partner;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione partner';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'partner';

    protected static ?string $pluralModelLabel = 'partner';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Anagrafica partner')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Select::make('user_id')
                        ->label('Account area partner')
                        ->relationship(
                            'user',
                            'email',
                            modifyQueryUsing: fn ($query) => $query->where('panel_role', 'partner'),
                        )
                        ->searchable()
                        ->preload()
                        ->unique(ignoreRecord: true),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                    TextInput::make('phone')->label('Telefono')->tel()->maxLength(50),
                    Toggle::make('active')->label('Attivo')->default(true),
                    Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Partner')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('user.email')->label('Accesso')->placeholder('Non collegato'),
                TextColumn::make('product_prices_count')->label('Prodotti')->counts('productPrices')->badge(),
                TextColumn::make('active')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attivo' : 'Disattivato')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            ProductPricesRelationManager::class,
            GoodsEntriesRelationManager::class,
            DailyReceiptsRelationManager::class,
            DailyWastesRelationManager::class,
            ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
