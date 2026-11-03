<?php

namespace App\Services\Documents;

use App\Services\Documents\Concerns\BuildsPdfLogo;
use App\Services\Reports\DailySummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DailySummaryPdfService
{
    use BuildsPdfLogo;

    public function __construct(private readonly DailySummaryService $summary) {}

    /**
     * @param  array<int, string>  $columns  Sottoinsieme di quelle in DailySummary::pdfColumnOptions().
     */
    public function stream(Carbon $date, bool $includePartners, array $columns): Response
    {
        $rows = $this->summary->build($date, $includePartners);
        $generatedAt = Carbon::now();

        return Pdf::loadView('pdf.daily-summary', [
            'rows' => $rows,
            'date' => $date,
            'includePartners' => $includePartners,
            'columns' => array_flip($columns),
            'totals' => [
                'net' => $rows->sum('net'),
                'tax' => $rows->sum('tax'),
                'gross' => $rows->sum('gross'),
            ],
            'logo' => $this->logoData(),
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait')->stream('resoconto-giornaliero-'.$date->format('Y-m-d').'.pdf');
    }
}
