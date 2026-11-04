<?php

namespace App\Filament\Pages;

use App\Models\DailySummaryOverride;
use App\Models\Product;
use App\Services\Reports\DailySummaryService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

    /**
     * @return array<string, mixed>
     */
    private function overrideFormSchema(): array
    {
        return [
            Select::make('product_id')
                ->label('Prodotto')
                ->options(Product::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->native(false),
            TextInput::make('quantity')
                ->label('Quantità totale')
                ->numeric()
                ->required(),
            TextInput::make('net')
                ->label('Costo netto')
                ->numeric()
                ->prefix('€')
                ->required(),
            TextInput::make('tax')
                ->label('IVA')
                ->numeric()
                ->prefix('€')
                ->required(),
            TextInput::make('gross')
                ->label('Costo totale')
                ->numeric()
                ->prefix('€')
                ->required(),
            Textarea::make('notes')
                ->label('Nota')
                ->rows(2),
        ];
    }

    public function editRowAction(): Action
    {
        return Action::make('editRow')
            ->label('Modifica')
            ->modalHeading('Modifica riga del resoconto')
            ->modalSubmitActionLabel('Salva correzione')
            ->schema($this->overrideFormSchema())
            ->fillForm(fn (array $arguments): array => [
                'product_id' => $arguments['productId'] ?? null,
                'quantity' => $arguments['quantity'] ?? null,
                'net' => $arguments['net'] ?? null,
                'tax' => $arguments['tax'] ?? null,
                'gross' => $arguments['gross'] ?? null,
            ])
            ->action(function (array $data): void {
                $this->saveOverride($data);
            });
    }

    public function addOverrideAction(): Action
    {
        return Action::make('addOverride')
            ->label('Aggiungi correzione')
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Aggiungi correzione manuale')
            ->modalSubmitActionLabel('Salva correzione')
            ->schema($this->overrideFormSchema())
            ->action(function (array $data): void {
                $this->saveOverride($data);
            });
    }

    public function deleteOverrideAction(): Action
    {
        return Action::make('deleteOverride')
            ->label('Annulla correzione')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Annullare la correzione manuale?')
            ->modalDescription('La riga tornerà a mostrare il valore calcolato dalle bolle reali.')
            ->action(function (array $arguments): void {
                DailySummaryOverride::query()
                    ->where('id', $arguments['overrideId'])
                    ->delete();

                Notification::make()
                    ->title('Correzione annullata')
                    ->success()
                    ->send();
            });
    }

    private function saveOverride(array $data): void
    {
        $product = Product::query()->find($data['product_id']);

        DailySummaryOverride::query()->updateOrCreate(
            [
                'date' => $this->date,
                'product_id' => $data['product_id'],
            ],
            [
                'name' => $product?->name ?? 'Prodotto',
                'symbol' => $product?->defaultUnitOfMeasure?->symbol,
                'quantity' => $data['quantity'],
                'net' => $data['net'],
                'tax' => $data['tax'],
                'gross' => $data['gross'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth('admin')->id(),
            ],
        );

        Notification::make()
            ->title('Correzione salvata')
            ->body('La riga mostrerà "Modificato manualmente" fino a quando non viene annullata.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->addOverrideAction(),
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
