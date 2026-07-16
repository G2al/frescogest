<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Actions\DeliveryDocumentActions;
use App\Filament\Resources\Orders\Actions\OrderDeleteAction;
use App\Filament\Resources\Orders\Actions\OrderPaymentActions;
use App\Filament\Resources\Orders\Actions\OrderStatusActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Ordini del cliente';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->orders()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')->label('Numero')->searchable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                IconColumn::make('paid_at')
                    ->label('Pagato')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => filled($record->paid_at)),
                TextColumn::make('deliveryDocument.document_number')->label('DDT')->placeholder('—'),
                TextColumn::make('requested_at')->label('Richiesto il')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                ...OrderStatusActions::make(),
                ...OrderPaymentActions::make(),
                ...DeliveryDocumentActions::make(),
                EditAction::make()->url(fn ($record): string => OrderResource::getUrl('edit', ['record' => $record])),
                OrderDeleteAction::make(),
            ]);
    }
}
