<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\RelationManagers\DailyReceiptsRelationManager;
use App\Filament\Resources\Partners\RelationManagers\DailyWastesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\DeliveryDocumentsRelationManager;
use App\Filament\Resources\Partners\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\GoodsEntriesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\ProductPricesRelationManager;
use App\Filament\Support\AdminOnlyResource;
use App\Models\Partner;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PartnerResource extends AdminOnlyResource
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
                    TextInput::make('email')
                        ->label('Email di accesso')
                        ->helperText('Il partner userà questa email per accedere all’area riservata.')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('account_password')
                        ->label('Password di accesso')
                        ->helperText('Obbligatoria alla creazione. In modifica lasciala vuota per mantenere quella attuale.')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required(fn (string $operation, ?Partner $record): bool => $operation === 'create' || ! $record?->user_id)
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    TextInput::make('phone')->label('Telefono')->tel()->maxLength(50),
                    Toggle::make('active')
                        ->label('Accesso partner attivo')
                        ->helperText('Disattivandolo il partner non potrà accedere al pannello.')
                        ->default(true),
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
            DeliveryDocumentsRelationManager::class,
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
