<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identità')
                    ->columnSpanFull()
                    ->description('Indicare la ragione sociale oppure nome e cognome.')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Ragione sociale')
                            ->requiredWithoutAll(['first_name', 'last_name'])
                            ->maxLength(255),
                        TextInput::make('first_name')
                            ->label('Nome')
                            ->requiredWithout('company_name')
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Cognome')
                            ->requiredWithout('company_name')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attivo')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Dati fiscali e contatti')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('vat_number')
                            ->label('Partita IVA')
                            ->maxLength(255),
                        TextInput::make('tax_code')
                            ->label('Codice fiscale')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Indirizzi')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('billing_address')
                            ->label('Indirizzo di fatturazione')
                            ->maxLength(255),
                        TextInput::make('delivery_address')
                            ->label('Indirizzo di consegna')
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
