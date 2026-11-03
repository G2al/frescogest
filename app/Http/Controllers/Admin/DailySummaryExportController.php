<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Documents\DailySummaryPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DailySummaryExportController extends Controller
{
    public function __invoke(Request $request, DailySummaryPdfService $pdf): Response
    {
        $user = $request->user('admin');
        abort_unless($user?->hasAdminPanelRole() === true, 403);

        $date = Carbon::parse($request->query('date') ?: now()->toDateString());
        $includePartners = $request->query('includePartners', '1') !== '0';

        return $pdf->stream($date, $includePartners);
    }
}
