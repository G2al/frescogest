<?php

namespace App\Services\Documents;

use App\Models\Customer;
use App\Models\DeliveryDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DeliveryDocumentFilenameService
{
    public function forDocument(DeliveryDocument $document): string
    {
        $document->loadMissing('order.customer');

        return $this->build(
            'bolla-'.$document->document_number,
            $document->order?->customer,
        );
    }

    public function forCollection(Collection $documents): string
    {
        $documents->each(
            fn (DeliveryDocument $document) => $document->loadMissing('order.customer'),
        );
        $customers = $documents
            ->pluck('order.customer')
            ->filter()
            ->unique('id');

        return $this->build(
            'bolle-consegna-'.now()->format('Ymd-His'),
            $customers->count() === 1 ? $customers->first() : null,
        );
    }

    private function build(string $prefix, ?Customer $customer): string
    {
        if (! $customer) {
            return Str::slug($prefix).'.pdf';
        }

        $customerName = Str::slug($customer->display_name) ?: 'cliente';
        $customerType = Str::slug($customer->type?->label() ?? 'cliente');

        return Str::slug($prefix.'-'.$customerName.'-'.$customerType).'.pdf';
    }
}
