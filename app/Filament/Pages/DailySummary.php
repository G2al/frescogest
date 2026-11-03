<?php

namespace App\Filament\Pages;

use App\Services\Reports\DailySummaryService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use UnitEnum;

class DailySummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Ordini';

    protected static ?string $navigationLabel = 'Resoconto giornaliero';

    protected static ?string $title = 'Resoconto giornaliero';

    protected string $view = 'filament.pages.daily-summary';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $date;

    public bool $includePartners = true;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true;
    }

    /**
     * @return Collection<int, object>
     */
    public function rows(): Collection
    {
        $date = CarbonImmutable::parse($this->date ?: now()->toDateString());

        return app(DailySummaryService::class)->build($date, $this->includePartners);
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'net' => $rows->sum('net'),
            'tax' => $rows->sum('tax'),
            'gross' => $rows->sum('gross'),
        ];
    }
}
