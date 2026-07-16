<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Metodo di pagamento')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attivo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
