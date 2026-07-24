<?php

namespace App\Filament\Partner\Resources\DailyWastes;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\DailyWastes\Pages\CreateDailyWaste;
use App\Filament\Partner\Resources\DailyWastes\Pages\EditDailyWaste;
use App\Filament\Partner\Resources\DailyWastes\Pages\ListDailyWastes;
use App\Models\PartnerDailyWaste;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyWasteResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = PartnerDailyWaste::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'Scarti giornalieri';

    protected static ?string $modelLabel = 'scarto';

    protected static ?string $pluralModelLabel = 'scarti giornalieri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Scarto giornaliero')->columns(2)->schema([
                DatePicker::make('waste_date')->label('Data')->default(today())->required(),
                TextInput::make('amount')->label('Valore dello scarto')->numeric()->minValue(0)->prefix('€')->required(),
                Textarea::make('notes')->label('Note')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('waste_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('amount')->label('Valore scarto')->money('EUR')->sortable(),
                TextColumn::make('notes')->label('Note')->limit(60),
            ])
            ->defaultSort('waste_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('partner_id', static::currentPartnerId());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyWastes::route('/'),
            'create' => CreateDailyWaste::route('/create'),
            'edit' => EditDailyWaste::route('/{record}/edit'),
        ];
    }
}
