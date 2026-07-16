<?php

namespace App\Filament\Resources\TaxRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aliquota IVA')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('percentage')
                            ->label('Percentuale')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Toggle::make('active')
                            ->label('Attiva')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
