<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $cards = [
            ['Ricavi netti', $summary['revenue'], 'Ordini pagati: '.$summary['ordersCount'], 'heroicon-o-banknotes', 'is-green'],
            ['Food cost netto', $summary['costOfGoods'], 'Costo della merce venduta', 'heroicon-o-shopping-bag', 'is-blue'],
            ['Margine lordo', $summary['grossMargin'], number_format($summary['marginPercentage'], 1, ',', '.').'% sui ricavi', 'heroicon-o-arrow-trending-up', 'is-amber'],
            ['Costi extra', $summary['extraCosts'], 'Movimenti registrati nel mese', 'heroicon-o-receipt-percent', 'is-violet'],
            ['Risultato reale', $summary['netResult'], 'Margine al netto dei costi extra', 'heroicon-o-scale', $summary['netResult'] >= 0 ? 'is-green' : 'is-red'],
        ];
        $products = $this->products();
        $categories = $this->categories();
        $customers = $this->customers();
    @endphp

    <div class="business-report">
        <section class="business-report-toolbar">
            <div class="business-report-toolbar-copy">
                <span class="business-report-toolbar-icon">
                    <x-heroicon-o-calendar-days />
                </span>
                <div>
                    <h2>Periodo di analisi</h2>
                    <p>Il report considera esclusivamente gli ordini pagati nel mese selezionato.</p>
                </div>
            </div>

            <div class="business-report-period">
                <label for="business-report-month">Mese analizzato</label>
                <input id="business-report-month" type="month" wire:model.live="month">
                <span class="business-report-loading" wire:loading wire:target="month">Aggiornamento dati…</span>
            </div>
        </section>

        <section class="business-report-cards" aria-label="Riepilogo economico">
            @foreach ($cards as [$label, $value, $description, $icon, $tone])
                <article class="business-report-card {{ $tone }}">
                    <span class="business-report-card-icon">
                        <x-dynamic-component :component="$icon" />
                    </span>
                    <div class="business-report-card-copy">
                        <span class="business-report-card-label">{{ $label }}</span>
                        <strong class="business-report-card-value">€ {{ number_format($value, 2, ',', '.') }}</strong>
                        <span class="business-report-card-description">{{ $description }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="business-report-grid">
            <section class="business-report-section is-wide">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-cube /></span>
                    <div>
                        <h2>Redditività per prodotto</h2>
                        <p>Quantità vendute, ricavi, food cost e margine di ogni prodotto.</p>
                    </div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th class="is-number">Quantità</th>
                                <th class="is-number">Ricavi netti</th>
                                <th class="is-number">Food cost</th>
                                <th class="is-number">Margine</th>
                                <th class="is-number">Margine %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $row)
                                @php($marginPercentage = (float) $row->revenue > 0 ? (float) $row->margin / (float) $row->revenue * 100 : 0)
                                <tr>
                                    <td class="is-name">{{ $row->product_name }}</td>
                                    <td class="is-number">{{ rtrim(rtrim(number_format($row->quantity, 3, ',', '.'), '0'), ',') }} {{ $row->unit_of_measure_symbol }}</td>
                                    <td class="is-number">€ {{ number_format($row->revenue, 2, ',', '.') }}</td>
                                    <td class="is-number">€ {{ number_format($row->cost, 2, ',', '.') }}</td>
                                    <td class="is-number"><strong>€ {{ number_format($row->margin, 2, ',', '.') }}</strong></td>
                                    <td class="is-number"><span class="business-report-margin">{{ number_format($marginPercentage, 1, ',', '.') }}%</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="business-report-empty">Nessun ordine pagato nel periodo selezionato.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="business-report-section">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-tag /></span>
                    <div>
                        <h2>Redditività per categoria</h2>
                        <p>Confronto economico tra le categorie vendute.</p>
                    </div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th class="is-number">Ricavi</th>
                                <th class="is-number">Costo</th>
                                <th class="is-number">Margine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $row)
                                <tr>
                                    <td class="is-name">{{ $row->name }}</td>
                                    <td class="is-number">€ {{ number_format($row->revenue, 2, ',', '.') }}</td>
                                    <td class="is-number">€ {{ number_format($row->cost, 2, ',', '.') }}</td>
                                    <td class="is-number"><span class="business-report-margin">€ {{ number_format($row->margin, 2, ',', '.') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="business-report-empty">Nessuna categoria disponibile nel periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="business-report-section">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-users /></span>
                    <div>
                        <h2>Forniture per cliente</h2>
                        <p>Clienti ordinati per valore delle forniture pagate.</p>
                    </div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="is-number">Ordini</th>
                                <th class="is-number">Ricavi</th>
                                <th class="is-number">Margine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $row)
                                <tr>
                                    <td class="is-name">{{ $row->company_name ?: trim($row->first_name.' '.$row->last_name) }}</td>
                                    <td class="is-number">{{ $row->orders_count }}</td>
                                    <td class="is-number">€ {{ number_format($row->revenue, 2, ',', '.') }}</td>
                                    <td class="is-number"><span class="business-report-margin">€ {{ number_format($row->margin, 2, ',', '.') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="business-report-empty">Nessuna fornitura disponibile nel periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
