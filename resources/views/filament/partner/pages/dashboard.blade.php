<x-filament-panels::page>
    @php
        $report = $this->report();
        $summary = $report['summary'];
        $money = fn ($value) => '€ '.number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="business-report">
        <section class="business-report-toolbar">
            <div class="business-report-toolbar-copy">
                <span class="business-report-toolbar-icon"><x-heroicon-o-chart-bar /></span>
                <div><h2>Andamento di Angela</h2><p>Riepilogo stimato basato sui dati registrati nel periodo.</p></div>
            </div>
            <div class="business-report-period">
                <label for="partner-period">Periodo</label>
                <select id="partner-period" wire:model.live="period">
                    <option value="week">Settimana</option>
                    <option value="month">Mese</option>
                </select>
            </div>
            <div class="business-report-period">
                <label for="partner-reference-date">Data di riferimento</label>
                <input id="partner-reference-date" type="date" wire:model.live="referenceDate">
            </div>
        </section>

        <section class="business-report-cards">
            @foreach ([
                ['Merce acquistata', $summary['purchases_gross'], 'IVA inclusa', 'heroicon-o-shopping-cart', 'is-blue'],
                ['Incassi', $summary['revenue_gross'], 'Totale lordo', 'heroicon-o-banknotes', 'is-green'],
                ['Scarti', $summary['waste_amount'], 'Perdite registrate', 'heroicon-o-trash', 'is-red'],
                ['Spese', $summary['expense_amount'], 'Costi aggiuntivi', 'heroicon-o-receipt-percent', 'is-violet'],
                ['Risultato stimato', $summary['estimated_result'], number_format($summary['estimated_margin_percentage'], 1, ',', '.').'% sugli incassi', 'heroicon-o-arrow-trending-up', $summary['estimated_result'] >= 0 ? 'is-green' : 'is-red'],
            ] as [$label, $value, $description, $icon, $tone])
                <article class="business-report-card {{ $tone }}">
                    <span class="business-report-card-icon"><x-dynamic-component :component="$icon" /></span>
                    <div class="business-report-card-copy">
                        <span class="business-report-card-label">{{ $label }}</span>
                        <strong class="business-report-card-value">{{ $money($value) }}</strong>
                        <span class="business-report-card-description">{{ $description }}</span>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
