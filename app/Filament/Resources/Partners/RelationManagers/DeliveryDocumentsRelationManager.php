<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use App\Filament\Resources\Partners\Actions\CreatePartnerDeliveryDocumentAction;
use App\Models\DeliveryDocument;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryDocuments';

    protected static ?string $title = 'Bolle di consegna';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('issued_at')->label('Emessa il')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('payment_method_snapshot')->label('Pagamento')->placeholder('Da concordare'),
                TextColumn::make('total_net')->label('Netto')->money('EUR'),
                TextColumn::make('total_tax')->label('IVA')->money('EUR'),
                TextColumn::make('total_gross')->label('IVA inclusa')->money('EUR'),
            ])
            ->defaultSort('issued_at', 'desc')
            ->headerActions([
                CreatePartnerDeliveryDocumentAction::make($this->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Scarica')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->tooltip('Scarica bolla')
                    ->url(fn (DeliveryDocument $record): string => route('admin.delivery-documents.show', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
