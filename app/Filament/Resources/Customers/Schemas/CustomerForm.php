<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cliente')
                ->columnSpanFull()
                ->schema([
                    Hidden::make('type')->default('private'),
                    TextInput::make('first_name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('last_name')
                        ->label('Cognome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(255),
                    Toggle::make('active')
                        ->label('Attivo')
                        ->default(true),
                ])
                ->columns(2),
            Section::make('Consegna')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('delivery_address')
                        ->label('Indirizzo')
                        ->maxLength(255),
                    TextInput::make('city')
                        ->label('Città')
                        ->maxLength(255),
                    TextInput::make('postal_code')
                        ->label('CAP')
                        ->maxLength(10),
                    TextInput::make('province')
                        ->label('Provincia')
                        ->maxLength(2),
                ])
                ->columns(2),
            Section::make('Note')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')
                        ->label('Note')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
