<?php

namespace App\Filament\Resources\CostCategories\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Movimenti';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('movement_date')->label('Data')->default(today())->required(),
            TextInput::make('amount')->label('Importo')->numeric()->minValue(0.01)->prefix('€')->required(),
            TextInput::make('description')->label('Descrizione')->required()->maxLength(255),
            Textarea::make('notes')->label('Note')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('movement_date')->label('Data')->date('d/m/Y')->sortable(),
            TextColumn::make('description')->label('Descrizione')->searchable(),
            TextColumn::make('amount')->label('Importo')->money('EUR')->sortable(),
        ])->defaultSort('movement_date', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
