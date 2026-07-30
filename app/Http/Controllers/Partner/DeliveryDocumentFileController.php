<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDocument;
use App\Services\Documents\DeliveryDocumentFilenameService;
use App\Services\Documents\DeliveryDocumentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDocumentFileController extends Controller
{
    public function __invoke(
        Request $request,
        DeliveryDocument $deliveryDocument,
        DeliveryDocumentPdfService $pdf,
        DeliveryDocumentFilenameService $filenames,
    ): Response {
        $partner = $request->user('admin')?->partner;

        abort_unless($partner?->active && $deliveryDocument->partner_id === $partner->getKey(), 403);

        return $pdf->stream(
            collect([$deliveryDocument]),
            $filenames->forDocument($deliveryDocument),
        );
    }
}
