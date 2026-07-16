<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati prodotto')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug((string) $state)))
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Codice')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Attivo')
                            ->default(true),
                        Toggle::make('is_public')
                            ->label('Pubblico nel catalogo')
                            ->default(false),
                        Toggle::make('is_seasonal')
                            ->label('Stagionale')
                            ->default(false),
                        TextInput::make('sort_order')
                            ->label('Ordine visualizzazione')
                            ->numeric()
                            ->default(0),
                        TextInput::make('price_per_kg')
                            ->label('Prezzo base')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(99999)
                            ->step(0.01)
                            ->prefix('€')
                            ->suffix('/kg')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('public_description')
                            ->label('Descrizione pubblica')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Immagine pubblica')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('catalog/products')
                            ->previewable()
                            ->openable()
                            ->downloadable()
                            ->imagePreviewHeight('250')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Classificazione')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_category_id')
                            ->label('Categoria')
                            ->relationship('productCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tax_rate_id')
                            ->label('Aliquota IVA')
                            ->relationship('taxRate', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('default_unit_of_measure_id')
                            ->label('Unità di misura predefinita')
                            ->relationship('defaultUnitOfMeasure', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
