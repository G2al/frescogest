<?php

namespace App\Filament\Partner\Resources\DeliveryDocuments;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Filament\Partner\Resources\DeliveryDocuments\Pages\ListDeliveryDocuments;
use App\Models\DeliveryDocument;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryDocumentResource extends Resource
{
    use ResolvesCurrentPartner;

    protected static ?string $model = DeliveryDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Le mie bolle';

    protected static ?string $modelLabel = 'bolla';

    protected static ?string $pluralModelLabel = 'bolle';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('issued_at')->label('Emessa il')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('payment_method_snapshot')->label('Pagamento')->placeholder('Da concordare'),
                TextColumn::make('total_net')->label('Netto')->money('EUR')->sortable(),
                TextColumn::make('total_tax')->label('IVA')->money('EUR')->sortable(),
                TextColumn::make('total_gross')->label('Totale')->money('EUR')->sortable(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordActions([
                Action::make('download')
                    ->label('Scarica')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (DeliveryDocument $record): string => route('partner.delivery-documents.show', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('partner_id', static::currentPartnerId());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListDeliveryDocuments::route('/')];
    }
}
