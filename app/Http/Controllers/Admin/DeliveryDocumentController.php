<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Documents\DeliveryDocumentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDocumentController extends Controller
{
    public function __invoke(Request $request, Order $order, DeliveryDocumentPdfService $pdf): Response
    {
        $user = $request->user('admin');
        abort_unless($user?->active && $user->can_access_panel, 403);

        $order->loadMissing(['deliveryDocument', 'paymentMethod']);
        abort_if(! $order->deliveryDocument, 404);

        return $pdf->stream(collect([$order->deliveryDocument]), $order->deliveryDocument->document_number.'.pdf');
    }
}
