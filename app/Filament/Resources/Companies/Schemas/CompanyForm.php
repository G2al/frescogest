<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati aziendali')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Ragione sociale')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('vat_number')
                            ->label('Partita IVA')
                            ->helperText('Da compilare quando sarà disponibile.')
                            ->maxLength(255),
                        TextInput::make('tax_code')
                            ->label('Codice fiscale')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attiva')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Contatti e sede')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Indirizzo')
                            ->maxLength(255)
                            ->columnSpanFull(),
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
                Section::make('Dati aggiuntivi')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(34),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('company-logos'),
                    ])
                    ->columns(2),
            ]);
    }
}
