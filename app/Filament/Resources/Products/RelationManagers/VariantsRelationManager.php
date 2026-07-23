<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Taglie e colori';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('size')
                ->label('Taglia')
                ->maxLength(30),
            TextInput::make('color')
                ->label('Colore')
                ->maxLength(80),
            Toggle::make('active')
                ->label('Disponibile')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('size')->label('Taglia')->sortable(),
                TextColumn::make('color')->label('Colore')->sortable(),
                ToggleColumn::make('active')->label('Disponibile'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nuova variante'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
