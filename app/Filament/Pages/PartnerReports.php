<?php

namespace App\Filament\Pages;

use App\Models\Partner;
use App\Services\Partners\PartnerReportService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use UnitEnum;

class PartnerReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|UnitEnum|null $navigationGroup = 'Gestione partner';

    protected static ?string $navigationLabel = 'Analisi partner';

    protected static ?string $title = 'Analisi partner';

    protected string $view = 'filament.pages.partner-reports';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?int $partnerId = null;

    public string $period = 'month';

    public string $referenceDate;

    public function mount(): void
    {
        $this->partnerId = Partner::query()->where('active', true)->value('id');
        $this->referenceDate = now()->toDateString();
    }

    public function partnerOptions(): array
    {
        return Partner::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function report(): array
    {
        $partner = Partner::query()->find($this->partnerId);

        if (! $partner) {
            return $this->emptyReport();
        }

        [$from, $to] = $this->range();

        return app(PartnerReportService::class)->build($partner, $from, $to);
    }

    public function periodLabel(): string
    {
        [$from, $to] = $this->range();

        return $from->format('d/m/Y').' – '.$to->format('d/m/Y');
    }

    private function range(): array
    {
        $date = CarbonImmutable::parse($this->referenceDate ?: now()->toDateString());

        return $this->period === 'week'
            ? [$date->startOfWeek(), $date->endOfWeek()]
            : [$date->startOfMonth(), $date->endOfMonth()];
    }

    private function emptyReport(): array
    {
        return [
            'summary' => array_fill_keys([
                'purchases_net',
                'purchases_tax',
                'purchases_gross',
                'revenue_gross',
                'waste_amount',
                'expense_amount',
                'estimated_result',
                'estimated_margin_percentage',
            ], 0),
            'products' => collect(),
            'receipts' => collect(),
            'wastes' => collect(),
            'expenses' => collect(),
        ];
    }
}
