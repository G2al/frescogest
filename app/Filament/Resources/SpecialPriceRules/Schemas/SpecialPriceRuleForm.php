<?php

namespace App\Filament\Resources\SpecialPriceRules\Schemas;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SpecialPriceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Regola di prezzo')
                ->description('Sovrascrive il ricarico predefinito del prodotto per il destinatario e l’ambito selezionati.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nome regola')
                        ->placeholder('Esempio: Frutta ristoratori +80%')
                        ->required()
                        ->maxLength(255),
                    Select::make('audience')
                        ->label('Destinatario')
                        ->options(SpecialPriceAudience::options())
                        ->default(SpecialPriceAudience::PrivateCustomers->value)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state !== SpecialPriceAudience::Partners->value) {
                                $set('partner_id', null);
                            }
                        }),
                    Select::make('partner_id')
                        ->label('Partner specifico')
                        ->relationship('partner', 'name', modifyQueryUsing: fn ($query) => $query->where('active', true))
                        ->placeholder('Tutti i partner')
                        ->helperText('Lascia vuoto per applicare la regola ad Angela e a tutti gli altri partner.')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('audience') === SpecialPriceAudience::Partners->value),
                    Select::make('scope_type')
                        ->label('Ambito')
                        ->options(SpecialPriceScope::options())
                        ->default(SpecialPriceScope::Category->value)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === SpecialPriceScope::Product->value) {
                                $set('product_category_id', null);
                            } else {
                                $set('product_id', null);
                            }
                        }),
                    Select::make('product_category_id')
                        ->label('Categoria')
                        ->relationship('productCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('scope_type') === SpecialPriceScope::Category->value)
                        ->visible(fn (Get $get): bool => $get('scope_type') === SpecialPriceScope::Category->value),
                    Select::make('product_id')
                        ->label('Prodotto')
                        ->relationship('product', 'name', modifyQueryUsing: fn ($query) => $query->where('active', true))
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('scope_type') === SpecialPriceScope::Product->value)
                        ->visible(fn (Get $get): bool => $get('scope_type') === SpecialPriceScope::Product->value),
                    TextInput::make('markup_percentage')
                        ->label('Ricarico sul costo di acquisto')
                        ->helperText('Esempio: costo 1,00 € e ricarico 80% producono un prezzo netto di 1,80 €')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->step(0.01)
                        ->suffix('%')
                        ->required(),
                    Toggle::make('active')
                        ->label('Regola attiva')
                        ->default(true),
                    Textarea::make('notes')
                        ->label('Note')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
