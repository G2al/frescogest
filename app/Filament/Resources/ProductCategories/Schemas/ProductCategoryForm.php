<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Categoria')
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
                        Toggle::make('active')
                            ->label('Attiva')
                            ->default(true),
                        Toggle::make('is_public')
                            ->label('Pubblica nel catalogo')
                            ->default(false),
                        TextInput::make('sort_order')
                            ->label('Ordine visualizzazione')
                            ->numeric()
                            ->default(0),
                        FileUpload::make('image_path')
                            ->label('Immagine pubblica')
                            ->image()
                            ->disk('public')
                            ->directory('catalog/categories'),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
