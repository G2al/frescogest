<?php

namespace App\Services\Documents;

use App\Models\DocumentSequence;
use Illuminate\Support\Carbon;

class DeliveryDocumentNumberService
{
    public function next(Carbon $issuedAt): string
    {
        $year = (int) $issuedAt->format('Y');
        $sequence = DocumentSequence::query()->firstOrCreate(
            ['document_type' => 'delivery_note', 'year' => $year],
            ['last_number' => 0],
        );
        $sequence = DocumentSequence::query()
            ->whereKey($sequence->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        $sequence->increment('last_number');

        return sprintf('BC-%d-%06d', $year, $sequence->fresh()->last_number);
    }
}
