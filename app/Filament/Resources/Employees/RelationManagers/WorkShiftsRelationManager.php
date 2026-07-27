<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkShiftsRelationManager extends RelationManager
{
    protected static string $relationship = 'workShifts';

    protected static ?string $title = 'Presenze e ore lavorate';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Turno di lavoro')
                ->columns(2)
                ->schema([
                    DatePicker::make('work_date')->label('Giorno')->default(today())->required(),
                    Select::make('status')
                        ->label('Stato')
                        ->options(['present' => 'Presente', 'absent' => 'Assente'])
                        ->default('present')
                        ->native(false)
                        ->live()
                        ->required(),
                    TextInput::make('break_minutes')
                        ->label('Pausa')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1439)
                        ->default(0)
                        ->suffix('minuti')
                        ->required()
                        ->visible(fn (Get $get): bool => $get('status') !== 'absent'),
                    TimePicker::make('started_at')
                        ->label('Inizio')
                        ->seconds(false)
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('status') !== 'absent')
                        ->visible(fn (Get $get): bool => $get('status') !== 'absent'),
                    TimePicker::make('ended_at')
                        ->label('Fine')
                        ->seconds(false)
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('status') !== 'absent')
                        ->visible(fn (Get $get): bool => $get('status') !== 'absent'),
                    Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'absent' ? 'Assente' : 'Presente')
                    ->color(fn (string $state): string => $state === 'absent' ? 'danger' : 'success'),
                TextColumn::make('started_at')->label('Ingresso')->time('H:i'),
                TextColumn::make('ended_at')->label('Uscita')->time('H:i'),
                TextColumn::make('break_minutes')->label('Pausa')->suffix(' min'),
                TextColumn::make('worked_duration')->label('Ore lavorate')->weight('bold'),
                TextColumn::make('expected_duration')->label('Ore previste'),
                TextColumn::make('variance_duration')
                    ->label('Differenza')
                    ->badge()
                    ->color(fn ($record): string => $record->variance_minutes >= 0 ? 'success' : 'danger'),
                TextColumn::make('pay_amount')->label('Compenso')->money('EUR'),
                TextColumn::make('notes')->label('Note')->limit(45)->toggleable(),
            ])
            ->filters([
                Filter::make('current_month')
                    ->label('Mese corrente')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('work_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),
            ])
            ->defaultSort('work_date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Registra presenza')
                    ->mutateDataUsing(fn (array $data): array => $this->withExpectedMinutes($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => $this->withExpectedMinutes($data)),
                DeleteAction::make(),
            ]);
    }

    private function withExpectedMinutes(array $data): array
    {
        $data['expected_minutes'] = (int) $this->getOwnerRecord()->expected_daily_minutes;

        return $data;
    }
}
