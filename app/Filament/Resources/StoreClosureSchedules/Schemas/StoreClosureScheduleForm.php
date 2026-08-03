<?php

namespace App\Filament\Resources\StoreClosureSchedules\Schemas;

use App\Enums\StoreClosureType;
use App\Models\StoreClosureSchedule;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StoreClosureScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Programmazione della chiusura')
                ->description('Le modifiche diventano effettive immediatamente sul frontend.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->placeholder('Aggiornamento quotidiano prezzi')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label('Tipo di chiusura')
                        ->options(StoreClosureType::options())
                        ->default(StoreClosureType::Recurring->value)
                        ->required()
                        ->live(),
                    CheckboxList::make('weekdays')
                        ->label('Giorni della settimana')
                        ->options(StoreClosureSchedule::weekdayOptions())
                        ->columns(4)
                        ->bulkToggleable()
                        ->required(fn (Get $get): bool => self::isType($get, StoreClosureType::Recurring))
                        ->visible(fn (Get $get): bool => self::isType($get, StoreClosureType::Recurring))
                        ->columnSpanFull(),
                    DatePicker::make('closure_date')
                        ->label(fn (Get $get): string => self::isType($get, StoreClosureType::FullDayRange) ? 'Dal giorno' : 'Data della chiusura')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(fn (Get $get): bool => self::isDatedType($get))
                        ->visible(fn (Get $get): bool => self::isDatedType($get)),
                    DatePicker::make('closure_end_date')
                        ->label('Al giorno compreso')
                        ->helperText('Il negozio riaprirà alle 00:00 del giorno successivo.')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(fn (Get $get): bool => self::isType($get, StoreClosureType::FullDayRange))
                        ->afterOrEqual('closure_date')
                        ->visible(fn (Get $get): bool => self::isType($get, StoreClosureType::FullDayRange)),
                    TimePicker::make('starts_at')
                        ->label('Chiuso dalle')
                        ->seconds(false)
                        ->required(fn (Get $get): bool => ! self::isType($get, StoreClosureType::FullDayRange))
                        ->visible(fn (Get $get): bool => ! self::isType($get, StoreClosureType::FullDayRange)),
                    TimePicker::make('ends_at')
                        ->label('Riapertura alle')
                        ->helperText('Se è precedente all’ora di chiusura, la riapertura avverrà il giorno successivo.')
                        ->seconds(false)
                        ->required(fn (Get $get): bool => ! self::isType($get, StoreClosureType::FullDayRange))
                        ->different('starts_at')
                        ->visible(fn (Get $get): bool => ! self::isType($get, StoreClosureType::FullDayRange)),
                    TextInput::make('message')
                        ->label('Messaggio per i clienti')
                        ->placeholder('Antonio sta aggiornando prezzi e disponibilità.')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('active')
                        ->label('Attivo')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    private static function isType(Get $get, StoreClosureType $type): bool
    {
        $state = $get('type');

        return ($state instanceof StoreClosureType ? $state->value : $state) === $type->value;
    }

    private static function isDatedType(Get $get): bool
    {
        return self::isType($get, StoreClosureType::SpecificDate)
            || self::isType($get, StoreClosureType::FullDayRange);
    }
}
