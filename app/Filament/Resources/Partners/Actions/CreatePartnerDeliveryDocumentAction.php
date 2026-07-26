<?php

namespace App\Filament\Resources\Partners\Actions;

use App\Models\Partner;
use App\Services\Documents\PartnerDeliveryDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class CreatePartnerDeliveryDocumentAction
{
    public static function make(Partner $partner): Action
    {
        return Action::make('createPartnerDeliveryDocument')
            ->label('Nuova bolla per Angela')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->modalHeading("Crea bolla per {$partner->name}")
            ->modalDescription('La merce inserita verrà registrata automaticamente anche nei carichi del partner.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Genera bolla')
            ->schema(PartnerDeliveryDocumentActionSchema::make($partner))
            ->action(function (array $data, Component $livewire) use ($partner): void {
                $document = app(PartnerDeliveryDocumentService::class)
                    ->create($partner, auth()->user(), $data);

                Notification::make()
                    ->success()
                    ->title('Bolla generata')
                    ->body("La {$document->document_number} è stata aggiunta anche alla merce caricata di {$partner->name}.")
                    ->send();

                $url = route('admin.delivery-documents.show', $document);
                $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
            });
    }
}
