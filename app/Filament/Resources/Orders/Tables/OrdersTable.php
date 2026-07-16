<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Actions\DeliveryDocumentActions;
use App\Filament\Resources\Orders\Actions\OrderDeleteAction;
use App\Filament\Resources\Orders\Actions\OrderPaymentActions;
use App\Filament\Resources\Orders\Actions\OrderStatusActions;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('customer.display_name')
                    ->label('Cliente')
                    ->searchable(['company_name', 'first_name', 'last_name']),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                IconColumn::make('paid_at')
                    ->label('Pagato')
                    ->boolean()
                    ->getStateUsing(fn (Order $record): bool => filled($record->paid_at)),
                TextColumn::make('deliveryDocument.document_number')
                    ->label('DDT')
                    ->placeholder('—'),
                TextColumn::make('items_count')->label('Righe')->counts('items'),
                TextColumn::make('total_amount')->label('Totale')->money('EUR')->sortable(),
                TextColumn::make('requested_at')->label('Richiesto il')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('expected_delivery_at')->label('Consegna prevista')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Stato')->options(OrderStatus::options()),
            ])
            ->defaultSort('requested_at', 'desc')
            ->recordActions([
                ...OrderStatusActions::make(),
                ...OrderPaymentActions::make(),
                ...DeliveryDocumentActions::make(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Order $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->customer->phone))
                    ->openUrlInNewTab()
                    ->visible(fn (Order $record): bool => filled($record->customer->phone)),
                EditAction::make(),
                OrderDeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
