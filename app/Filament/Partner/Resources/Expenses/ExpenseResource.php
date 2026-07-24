<?php

namespace App\Filament\Partner\Resources\Expenses;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Partner\Resources\Expenses\Pages\EditExpense;
use App\Filament\Partner\Resources\Expenses\Pages\ListExpenses;
use App\Models\PartnerExpense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = PartnerExpense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Spese';

    protected static ?string $modelLabel = 'spesa';

    protected static ?string $pluralModelLabel = 'spese';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Spesa')->columns(2)->schema([
                DatePicker::make('expense_date')->label('Data')->default(today())->required(),
                TextInput::make('amount')->label('Importo')->numeric()->minValue(0)->prefix('€')->required(),
                TextInput::make('description')->label('Descrizione')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('notes')->label('Note')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->label('Descrizione')->searchable(),
                TextColumn::make('amount')->label('Importo')->money('EUR')->sortable(),
            ])
            ->defaultSort('expense_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('partner_id', static::currentPartnerId());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
