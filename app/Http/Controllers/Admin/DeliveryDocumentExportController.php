<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDocument;
use App\Services\Documents\DeliveryDocumentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDocumentExportController extends Controller
{
    public function __invoke(Request $request, DeliveryDocumentPdfService $pdf): Response
    {
        $user = $request->user('admin');
        abort_unless($user?->active && $user->can_access_panel, 403);

        $ids = collect(explode(',', (string) $request->query('documents')))->filter()->map(fn ($id) => (int) $id)->unique();
        abort_if($ids->isEmpty(), 404);

        $documents = DeliveryDocument::query()->with(['order.customer', 'order.paymentMethod'])->whereKey($ids)->orderBy('issued_at')->get();
        abort_if($documents->isEmpty(), 404);

        return $pdf->stream($documents, 'bolle-consegna-'.now()->format('Ymd-His').'.pdf');
    }
}
