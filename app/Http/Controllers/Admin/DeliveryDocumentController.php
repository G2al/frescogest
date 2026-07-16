<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDocumentController extends Controller
{
    public function __invoke(Request $request, Order $order): Response
    {
        abort_unless($request->user()?->active && $request->user()?->can_access_panel, 403);

        $order->loadMissing(['deliveryDocument', 'paymentMethod']);
        $document = $order->deliveryDocument;
        abort_if(! $document, 404);

        return Pdf::loadView('pdf.delivery-document', [
            'document' => $document,
            'order' => $order,
            'logo' => $this->logoDataUri($document->sender_snapshot['logo_path'] ?? null),
        ])
            ->setPaper('a4')
            ->stream($document->document_number.'.pdf');
    }

    private function logoDataUri(?string $logoPath): ?string
    {
        $paths = array_filter([
            $logoPath ? storage_path('app/public/'.$logoPath) : null,
            $logoPath ? public_path('storage/'.$logoPath) : null,
            public_path('assets/images/frescogest-logo.png'),
        ]);

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return null;
    }
}
