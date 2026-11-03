<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Pages\DailySummary;
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

        $allowedColumns = array_keys(DailySummary::pdfColumnOptions());
        $requestedColumns = array_filter(explode(',', (string) $request->query('columns', '')));
        $matchedColumns = array_values(array_intersect($allowedColumns, $requestedColumns));
        $columns = $matchedColumns === [] ? $allowedColumns : $matchedColumns;

        return $pdf->stream($date, $includePartners, $columns);
    }
}
