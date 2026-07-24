<?php

namespace App\Filament\Resources\Partners\RelationManagers;

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

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Spese di Angela';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('expense_date')->label('Data')->default(today())->required(),
            TextInput::make('description')->label('Descrizione')->required()->maxLength(255),
            TextInput::make('amount')->label('Importo')->numeric()->minValue(0)->step(0.01)->prefix('€')->required(),
            Textarea::make('notes')->label('Note')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->label('Descrizione')->searchable(),
                TextColumn::make('amount')->label('Importo')->money('EUR')->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
