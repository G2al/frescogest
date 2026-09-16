<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Documents\ProductCatalogPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductCatalogExportController extends Controller
{
    public function __invoke(Request $request, ProductCatalogPdfService $pdf): Response
    {
        $user = $request->user('admin');
        abort_unless($user?->hasAdminPanelRole() === true, 403);

        return $pdf->stream();
    }
}
