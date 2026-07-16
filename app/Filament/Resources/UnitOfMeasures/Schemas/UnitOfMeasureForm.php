<?php

namespace App\Filament\Resources\UnitOfMeasures\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitOfMeasureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Unità di misura')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label('Simbolo')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('type')
                            ->label('Tipo')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attiva')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
