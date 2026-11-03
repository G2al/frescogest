<?php

namespace App\Filament\Pages;

use App\Services\Reports\DailySummaryService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Component;
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
     * Colonne selezionabili per il PDF (oltre a "Prodotto", sempre presente).
     *
     * @return array<string, string>
     */
    public static function pdfColumnOptions(): array
    {
        return [
            'quantity' => 'Quantità totale',
            'net' => 'Costo netto',
            'tax' => 'IVA',
            'gross' => 'Costo totale',
            'source' => 'Origine',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Scarica PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Scegli cosa stampare nel PDF')
                ->modalSubmitActionLabel('Genera PDF')
                ->schema([
                    CheckboxList::make('columns')
                        ->label('Colonne da includere')
                        ->helperText('"Prodotto" è sempre presente. Seleziona solo quello che ti serve stampare.')
                        ->options(self::pdfColumnOptions())
                        ->default(array_keys(self::pdfColumnOptions()))
                        ->columns(2)
                        ->required(),
                ])
                ->action(function (array $data, Component $livewire): void {
                    $url = route('admin.daily-summary.pdf', [
                        'date' => $this->date,
                        'includePartners' => $this->includePartners ? 1 : 0,
                        'columns' => implode(',', $data['columns']),
                    ]);
                    $livewire->js('window.open('.json_encode($url).', "_blank", "noopener,noreferrer")');
                }),
        ];
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
