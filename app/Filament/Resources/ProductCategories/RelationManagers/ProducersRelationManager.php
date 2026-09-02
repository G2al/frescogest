<?php

namespace App\Filament\Resources\ProductCategories\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProducersRelationManager extends RelationManager
{
    protected static string $relationship = 'producers';

    protected static ?string $title = 'Case produttrici';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome casa produttrice')
                ->required()
                ->maxLength(255),
            TextInput::make('sort_order')
                ->label('Ordine visualizzazione')
                ->numeric()
                ->default(0),
            Toggle::make('active')
                ->label('Attiva')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('products_count')->label('Vini/prodotti assegnati')->counts('products'),
                ToggleColumn::make('active')->label('Attiva'),
                TextColumn::make('sort_order')->label('Ordine')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('Nuova casa produttrice'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
