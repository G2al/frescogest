<?php

namespace App\Filament\Resources\Partners\Actions;

use App\Models\DeliveryDocument;
use App\Services\Documents\PartnerDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class EditPartnerDeliveryDocumentAction
{
    public static function make(): Action
    {
        return Action::make('editPartnerDeliveryDocument')
            ->label('Modifica')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->iconButton()
            ->tooltip('Modifica e rigenera bolla')
            ->visible(fn (DeliveryDocument $record): bool => filled($record->partner_id) && blank($record->order_id))
            ->modalHeading(fn (DeliveryDocument $record): string => "Modifica {$record->document_number}")
            ->modalDescription('Salvando le modifiche verrà generata una nuova revisione. Qualsiasi copia precedente della bolla non sarà più valida.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Rigenera bolla')
            ->schema(fn (DeliveryDocument $record): array => PartnerDeliveryDocumentActionSchema::make(
                $record->partner()->firstOrFail(),
            ))
            ->fillForm(fn (DeliveryDocument $record): array => [
                'issued_at' => $record->issued_at,
                'payment_method_snapshot' => $record->payment_method_snapshot,
                'items' => collect($record->items_snapshot)
                    ->map(fn (array $item): array => [
                        'product_id' => $item['product_id'] ?? null,
                        'quantity' => $item['quantity'] ?? null,
                        'unit_price_net' => $item['unit_price_net'] ?? null,
                        'unit_symbol' => $item['unit_symbol'] ?? null,
                    ])
                    ->all(),
                'notes' => $record->notes,
            ])
            ->action(function (DeliveryDocument $record, array $data, Component $livewire): void {
                $document = app(PartnerDeliveryDocumentService::class)
                    ->update($record, auth()->user(), $data);

                Notification::make()
                    ->success()
                    ->title('Bolla rigenerata')
                    ->body("Creata la revisione {$document->revision}. La versione precedente non è più valida.")
                    ->send();

                $url = route('admin.delivery-documents.show', $document);
                $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
            });
    }
}
