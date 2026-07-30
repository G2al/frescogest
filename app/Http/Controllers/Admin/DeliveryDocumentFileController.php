<?php

namespace App\Http\Controllers\Admin;

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
        $user = $request->user('admin');
        abort_unless($user?->active && $user->can_access_panel, 403);
        abort_if(
            $user->hasPartnerPanelRole()
            && $deliveryDocument->partner_id !== $user->partner->getKey(),
            403,
        );

        $deliveryDocument->loadMissing(['order.customer', 'partner']);

        return $pdf->stream(
            collect([$deliveryDocument]),
            $filenames->forDocument($deliveryDocument),
        );
    }
}
