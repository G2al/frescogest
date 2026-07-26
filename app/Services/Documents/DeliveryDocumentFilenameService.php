<?php

namespace App\Services\Documents;

use App\Models\DeliveryDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DeliveryDocumentFilenameService
{
    public function forDocument(DeliveryDocument $document): string
    {
        if ($document->exists) {
            $document->loadMissing(['order.customer', 'partner']);
        }

        return $this->build(
            'bolla-'.$document->document_number,
            $document->recipient_name,
            $document->partner_id
                ? 'partner'
                : ($document->order?->customer?->type?->label() ?? 'cliente'),
        );
    }

    public function forCollection(Collection $documents): string
    {
        $documents->each(
            fn (DeliveryDocument $document) => $document->exists
                ? $document->loadMissing(['order.customer', 'partner'])
                : $document,
        );
        $recipients = $documents
            ->map(fn (DeliveryDocument $document): array => [
                'name' => $document->recipient_name,
                'type' => $document->partner_id
                    ? 'partner'
                    : ($document->order?->customer?->type?->label() ?? 'cliente'),
            ])
            ->unique(fn (array $recipient): string => $recipient['type'].'|'.$recipient['name'])
            ->values();

        return $this->build(
            'bolle-consegna-'.now()->format('Ymd-His'),
            $recipients->count() === 1 ? $recipients->first()['name'] : null,
            $recipients->count() === 1 ? $recipients->first()['type'] : null,
        );
    }

    private function build(string $prefix, ?string $recipientName, ?string $recipientType): string
    {
        if (! $recipientName) {
            return Str::slug($prefix).'.pdf';
        }

        $name = Str::slug($recipientName) ?: 'destinatario';
        $type = Str::slug($recipientType ?? 'cliente');

        return Str::slug($prefix.'-'.$name.'-'.$type).'.pdf';
    }
}
