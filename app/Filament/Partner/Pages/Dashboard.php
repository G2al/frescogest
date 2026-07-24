<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Concerns\ResolvesCurrentPartner;
use App\Services\Partners\PartnerReportService;
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
        $date = CarbonImmutable::parse($this->referenceDate ?: now()->toDateString());
        [$from, $to] = $this->period === 'week'
            ? [$date->startOfWeek(), $date->endOfWeek()]
            : [$date->startOfMonth(), $date->endOfMonth()];

        return app(PartnerReportService::class)->build(static::currentPartner(), $from, $to);
    }
}
