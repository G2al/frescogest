<?php

namespace App\Filament\Resources\Employees;

use App\Enums\EmployeeCompensationType;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\RelationManagers\WorkShiftsRelationManager;
use App\Filament\Support\AdminOnlyResource;
use App\Models\Employee;
use App\Services\Employees\EmployeeCostService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends AdminOnlyResource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Personale';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'dipendente';

    protected static ?string $pluralModelLabel = 'dipendenti';

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Anagrafica')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('first_name')->label('Nome')->required()->maxLength(255),
                    TextInput::make('last_name')->label('Cognome')->required()->maxLength(255),
                    TextInput::make('email')->label('Email di accesso')->email()->required()->maxLength(255),
                    TextInput::make('phone')->label('Telefono')->tel()->required()->maxLength(50),
                    TextInput::make('account_password')
                        ->label('Password di accesso')
                        ->helperText('Obbligatoria alla creazione. In modifica lasciala vuota per non cambiarla.')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(),
                    FileUpload::make('photo_path')
                        ->label('Foto dipendente')
                        ->helperText('Facoltativa. Formati immagine comuni, massimo 5 MB.')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('employees')
                        ->maxSize(5120)
                        ->imageEditor()
                        ->columnSpanFull(),
                    DatePicker::make('hired_on')->label('Data di assunzione')->default(today())->required(),
                    DatePicker::make('terminated_on')
                        ->label('Data di cessazione')
                        ->afterOrEqual('hired_on'),
                    Toggle::make('active')->label('Dipendente attivo')->default(true),
                ]),
            Section::make('Contratto e orario')
                ->description('La paga alimenta automaticamente il costo del personale nell’analisi economica.')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Select::make('compensation_type')
                        ->label('Tipo di paga')
                        ->options(EmployeeCompensationType::options())
                        ->default(EmployeeCompensationType::Daily->value)
                        ->native(false)
                        ->required(),
                    TextInput::make('compensation_amount')
                        ->label('Importo della paga')
                        ->helperText('Importo per ora, giornata oppure mese, in base al tipo selezionato.')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('€')
                        ->required(),
                    TextInput::make('expected_daily_minutes')
                        ->label('Ore previste al giorno')
                        ->helperText('Esempio: 8 oppure 7,5 ore.')
                        ->default(480)
                        ->numeric()
                        ->minValue(0.25)
                        ->maxValue(24)
                        ->step(0.25)
                        ->suffix('ore')
                        ->formatStateUsing(fn ($state): float => round(((int) $state) / 60, 2))
                        ->dehydrateStateUsing(fn ($state): int => (int) round((float) $state * 60))
                        ->required(),
                    Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (Employee $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name).'&background=dff5ea&color=053f32'),
                TextColumn::make('full_name')->label('Dipendente')->searchable(['first_name', 'last_name'])->sortable(['last_name', 'first_name']),
                TextColumn::make('email')->label('Account')->searchable()->description(fn (Employee $record): string => $record->user ? 'Accesso attivo' : 'Account non creato'),
                TextColumn::make('compensation_type')
                    ->label('Paga')
                    ->badge()
                    ->formatStateUsing(fn (EmployeeCompensationType $state): string => $state->label()),
                TextColumn::make('compensation_amount')->label('Importo')->money('EUR')->sortable(),
                TextColumn::make('expected_daily_minutes')
                    ->label('Orario previsto')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 60, 2, ',', '.').' ore'),
                TextColumn::make('work_shifts_count')->label('Presenze')->counts('workShifts')->badge(),
                TextColumn::make('current_month_hours')
                    ->label('Ore del mese')
                    ->state(function (Employee $record): string {
                        $minutes = app(EmployeeCostService::class)->workedMinutesForMonth($record, now()->year, now()->month);

                        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
                    }),
                TextColumn::make('current_month_cost')
                    ->label('Costo del mese')
                    ->state(fn (Employee $record): float => app(EmployeeCostService::class)->forEmployeeMonth($record, now()->year, now()->month))
                    ->money('EUR'),
                TextColumn::make('hired_on')->label('Assunto il')->date('d/m/Y')->sortable(),
                ToggleColumn::make('active')->label('Attivo'),
            ])
            ->filters([
                SelectFilter::make('compensation_type')
                    ->label('Tipo di paga')
                    ->options(EmployeeCompensationType::options()),
            ])
            ->defaultSort('last_name');
    }

    public static function getRelations(): array
    {
        return [WorkShiftsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
