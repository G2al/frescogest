<?php

namespace App\Filament\Resources\PromotionCodes\Schemas;

use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Promozione')
                ->description('Configura il codice, lo sconto e i clienti che possono utilizzarlo.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nome promozione')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('Codice sconto')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText('Verrà salvato automaticamente in maiuscolo.'),
                    TextInput::make('discount_percentage')
                        ->label('Sconto percentuale')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required(),
                    Select::make('audience')
                        ->label('Destinatari')
                        ->options(PromotionAudience::options())
                        ->default(PromotionAudience::Everyone->value)
                        ->required(),
                    Select::make('rule')
                        ->label('Regola di utilizzo')
                        ->options(PromotionRule::options())
                        ->default(PromotionRule::FirstOrder->value)
                        ->required(),
                    Toggle::make('single_use_per_customer')
                        ->label('Un solo utilizzo per cliente')
                        ->default(true),
                ])
                ->columns(2),
            Section::make('Validità')
                ->description('Lascia vuote le date per non impostare limiti temporali.')
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Attivo dal')
                        ->seconds(false),
                    DateTimePicker::make('ends_at')
                        ->label('Attivo fino al')
                        ->seconds(false)
                        ->afterOrEqual('starts_at'),
                    Toggle::make('active')
                        ->label('Codice attivo')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
