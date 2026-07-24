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

class DailyReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'dailyReceipts';

    protected static ?string $title = 'Incassi giornalieri';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('receipt_date')->label('Data')->default(today())->required(),
            TextInput::make('gross_amount')->label('Incasso IVA inclusa')->numeric()->minValue(0)->step(0.01)->prefix('€')->required(),
            Textarea::make('notes')->label('Note')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('gross_amount')->label('Incasso')->money('EUR')->sortable(),
                TextColumn::make('notes')->label('Note')->limit(60),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
