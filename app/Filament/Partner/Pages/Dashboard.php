<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Services\Partners\PartnerReportService;
use App\Services\Reports\TrendService;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class Dashboard extends Page
{
    use ResolvesCurrentPartner;

    protected static string $routePath = '/';

    protected static ?string $title = 'Riepilogo attività';

    protected static ?string $navigationLabel = 'Riepilogo';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.partner.pages.dashboard';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $period = 'month';

    public string $referenceDate;

    public function mount(): void
    {
        $this->referenceDate = now()->toDateString();
    }

    public function report(): array
    {
        [$from, $to] = $this->range();

        return app(PartnerReportService::class)->build(static::currentPartner(), $from, $to);
    }

    public function trends(): array
    {
        [$from, $to] = $this->range();
        [$previousFrom, $previousTo] = $this->period === 'week'
            ? [$from->subWeek(), $to->subWeek()]
            : [$from->subMonthNoOverflow()->startOfMonth(), $from->subMonthNoOverflow()->endOfMonth()];
        $service = app(PartnerReportService::class);
        $partner = static::currentPartner();
        $current = $service->build($partner, $from, $to)['summary'];
        $previous = $service->build($partner, $previousFrom, $previousTo)['summary'];
        $lowerIsBetter = ['purchases_gross', 'waste_amount', 'expense_amount'];

        return collect($current)
            ->only(['purchases_gross', 'revenue_gross', 'waste_amount', 'expense_amount', 'estimated_result'])
            ->mapWithKeys(fn ($value, string $key): array => [
                $key => app(TrendService::class)->compare(
                    (float) $value,
                    (float) ($previous[$key] ?? 0),
                    in_array($key, $lowerIsBetter, true),
                ),
            ])
            ->all();
    }

    private function range(): array
    {
        $date = CarbonImmutable::parse($this->referenceDate ?: now()->toDateString());

        return $this->period === 'week'
            ? [$date->startOfWeek(), $date->endOfWeek()]
            : [$date->startOfMonth(), $date->endOfMonth()];
    }
}
