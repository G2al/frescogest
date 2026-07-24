<?php

namespace App\Filament\Partner\Resources\DailyReceipts;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\DailyReceipts\Pages\CreateDailyReceipt;
use App\Filament\Partner\Resources\DailyReceipts\Pages\EditDailyReceipt;
use App\Filament\Partner\Resources\DailyReceipts\Pages\ListDailyReceipts;
use App\Models\PartnerDailyReceipt;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyReceiptResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = PartnerDailyReceipt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Incassi giornalieri';

    protected static ?string $modelLabel = 'incasso';

    protected static ?string $pluralModelLabel = 'incassi giornalieri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incasso giornaliero')->columns(2)->schema([
                DatePicker::make('receipt_date')->label('Data')->default(today())->required(),
                TextInput::make('gross_amount')->label('Incasso IVA inclusa')->numeric()->minValue(0)->prefix('€')->required(),
                Textarea::make('notes')->label('Note')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('gross_amount')->label('Incasso IVA inclusa')->money('EUR')->sortable(),
                TextColumn::make('notes')->label('Note')->limit(60),
            ])
            ->defaultSort('receipt_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('partner_id', static::currentPartnerId());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyReceipts::route('/'),
            'create' => CreateDailyReceipt::route('/create'),
            'edit' => EditDailyReceipt::route('/{record}/edit'),
        ];
    }
}
