<?php

namespace App\Filament\Resources\DeliveryDocuments\Actions;

use App\Models\DeliveryDocument;
use App\Services\Documents\OrderDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class EditOrderDeliveryDocumentAction
{
    public static function make(): Action
    {
        return Action::make('editOrderDeliveryDocument')
            ->label('Modifica')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->iconButton()
            ->tooltip('Modifica e rigenera bolla')
            ->modalHeading(fn (DeliveryDocument $record): string => "Modifica {$record->document_number}")
            ->modalDescription('Salvando le modifiche verranno aggiornati sia la bolla sia l’ordine collegato, e verrà generata una nuova revisione. Qualsiasi copia precedente della bolla non sarà più valida.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Rigenera bolla')
            ->schema(fn (DeliveryDocument $record): array => OrderDeliveryDocumentActionSchema::make(
                $record->order()->firstOrFail(),
            ))
            ->fillForm(fn (DeliveryDocument $record): array => [
                'issued_at' => $record->issued_at,
                'payment_method_id' => $record->order?->payment_method_id,
                'discount_percentage' => $record->order?->discount_percentage ?? 0,
                'notes' => $record->order?->internal_notes,
                'items' => $record->order?->items
                    ->map(fn ($item): array => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price_net' => $item->unit_price_net,
                        'unit_symbol' => $item->unit_of_measure_symbol,
                    ])
                    ->all() ?? [],
            ])
            ->action(function (DeliveryDocument $record, array $data, Component $livewire): void {
                $document = app(OrderDeliveryDocumentService::class)->update($record, $data);

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
