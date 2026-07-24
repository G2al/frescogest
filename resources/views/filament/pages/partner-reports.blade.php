<x-filament-panels::page>
    @php
        $report = $this->report();
        $summary = $report['summary'];
        $money = fn ($value) => '€ '.number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="business-report">
        <section class="business-report-toolbar">
            <div class="business-report-toolbar-copy">
                <span class="business-report-toolbar-icon"><x-heroicon-o-user-group /></span>
                <div>
                    <h2>Periodo e partner</h2>
                    <p>Confronta merce caricata, incassi, scarti e spese nel periodo selezionato.</p>
                </div>
            </div>
            <div class="business-report-period">
                <label for="partner-report-partner">Partner</label>
                <select id="partner-report-partner" wire:model.live="partnerId">
                    @foreach ($this->partnerOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="business-report-period">
                <label for="partner-report-period">Raggruppamento</label>
                <select id="partner-report-period" wire:model.live="period">
                    <option value="week">Settimana</option>
                    <option value="month">Mese</option>
                </select>
            </div>
            <div class="business-report-period">
                <label for="partner-report-date">Data di riferimento</label>
                <input id="partner-report-date" type="date" wire:model.live="referenceDate">
                <span class="business-report-loading">{{ $this->periodLabel() }}</span>
            </div>
        </section>

        <section class="business-report-cards">
            @foreach ([
                ['Merce acquistata', $summary['purchases_gross'], 'IVA inclusa', 'heroicon-o-shopping-cart', 'is-blue'],
                ['Incassi registrati', $summary['revenue_gross'], 'Importi giornalieri lordi', 'heroicon-o-banknotes', 'is-green'],
                ['Scarti', $summary['waste_amount'], 'Perdita registrata', 'heroicon-o-trash', 'is-red'],
                ['Spese', $summary['expense_amount'], 'Costi aggiuntivi', 'heroicon-o-receipt-percent', 'is-violet'],
                ['Risultato stimato', $summary['estimated_result'], number_format($summary['estimated_margin_percentage'], 1, ',', '.').'% sugli incassi', 'heroicon-o-chart-bar', $summary['estimated_result'] >= 0 ? 'is-green' : 'is-red'],
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

        <div class="business-report-grid">
            <section class="business-report-section is-wide">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-cube /></span>
                    <div><h2>Merce caricata per prodotto</h2><p>Valore effettivamente fornito da Antonio ad Angela.</p></div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead><tr><th>Prodotto</th><th class="is-number">Quantità</th><th class="is-number">Netto</th><th class="is-number">IVA</th><th class="is-number">Totale</th></tr></thead>
                        <tbody>
                            @forelse ($report['products'] as $row)
                                <tr>
                                    <td class="is-name">{{ $row->name }}</td>
                                    <td class="is-number">{{ rtrim(rtrim(number_format($row->quantity, 3, ',', '.'), '0'), ',') }} {{ $row->symbol }}</td>
                                    <td class="is-number">{{ $money($row->net) }}</td>
                                    <td class="is-number">{{ $money($row->tax) }}</td>
                                    <td class="is-number"><strong>{{ $money($row->gross) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="business-report-empty">Nessun carico nel periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @foreach ([
                ['Incassi giornalieri', $report['receipts'], 'receipt_date', 'gross_amount'],
                ['Scarti giornalieri', $report['wastes'], 'waste_date', 'amount'],
                ['Spese', $report['expenses'], 'expense_date', 'amount'],
            ] as [$title, $rows, $dateField, $amountField])
                <section class="business-report-section">
                    <header class="business-report-section-heading">
                        <span class="business-report-section-icon"><x-heroicon-o-calendar-days /></span>
                        <div><h2>{{ $title }}</h2><p>Movimenti inseriti nel periodo.</p></div>
                    </header>
                    <div class="business-report-table-wrap">
                        <table class="business-report-table">
                            <thead><tr><th>Data</th><th class="is-number">Importo</th></tr></thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr><td class="is-name">{{ $row->{$dateField}->format('d/m/Y') }}</td><td class="is-number"><strong>{{ $money($row->{$amountField}) }}</strong></td></tr>
                                @empty
                                    <tr><td colspan="2" class="business-report-empty">Nessun movimento.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
