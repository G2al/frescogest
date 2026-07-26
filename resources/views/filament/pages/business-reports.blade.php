<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $trends = $this->trends();
        $activityDescription = 'Ordini pagati: '.$summary['ordersCount'].' · Forniture partner: '.$summary['partnerSuppliesCount'];
        $cards = [
            ['revenue', 'Ricavi netti', $summary['revenue'], $activityDescription, 'heroicon-o-banknotes', 'is-green'],
            ['grossRevenue', 'Ricavi IVA inclusa', $summary['grossRevenue'], 'Totale realmente incassato', 'heroicon-o-currency-euro', 'is-teal'],
            ['tax', 'IVA sulle vendite', $summary['tax'], 'IVA complessiva del periodo', 'heroicon-o-receipt-percent', 'is-red'],
            ['purchaseTax', 'IVA sugli acquisti', $summary['purchaseTax'], 'IVA compresa nei costi della merce', 'heroicon-o-arrow-down-tray', 'is-blue'],
            ['vatBalance', 'Saldo IVA', $summary['vatBalance'], 'IVA vendite meno IVA acquisti', 'heroicon-o-scale', $summary['vatBalance'] >= 0 ? 'is-amber' : 'is-green'],
            ['discounts', 'Sconti concessi', $summary['discounts'], 'Riduzione netta applicata in bolla', 'heroicon-o-tag', 'is-pink'],
            ['costOfGoods', 'Food cost netto', $summary['costOfGoods'], 'Costo della merce venduta', 'heroicon-o-shopping-bag', 'is-cyan'],
            ['grossMargin', 'Margine lordo', $summary['grossMargin'], number_format($summary['marginPercentage'], 1, ',', '.').'% sui ricavi', 'heroicon-o-arrow-trending-up', 'is-orange'],
            ['extraCosts', 'Costi extra', $summary['extraCosts'], 'Movimenti registrati nel mese', 'heroicon-o-receipt-percent', 'is-violet'],
            ['personnelCosts', 'Costo del personale', $summary['personnelCosts'], 'Paghe giornaliere e mensili del periodo', 'heroicon-o-identification', 'is-indigo'],
            ['operatingCosts', 'Costi operativi', $summary['operatingCosts'], 'Costi extra e costo del personale', 'heroicon-o-calculator', 'is-slate'],
            ['netResult', 'Risultato reale', $summary['netResult'], 'Margine al netto di tutti i costi operativi', 'heroicon-o-scale', $summary['netResult'] >= 0 ? 'is-green' : 'is-red'],
        ];
        $products = $this->products();
        $categories = $this->categories();
        $customers = $this->customers();
        $taxBreakdown = $this->taxBreakdown();
    @endphp

    <div class="business-report">
        <section class="business-report-toolbar">
            <div class="business-report-toolbar-copy">
                <span class="business-report-toolbar-icon">
                    <x-heroicon-o-calendar-days />
                </span>
                <div>
                    <h2>Periodo di analisi</h2>
                    <p>Confronta ordini pagati e forniture ai partner senza duplicare gli incassi dei partner.</p>
                </div>
            </div>

            <div class="business-report-period">
                <label for="business-report-month">Mese analizzato</label>
                <input id="business-report-month" type="month" wire:model.live="month">
                <span class="business-report-loading" wire:loading wire:target="month">Aggiornamento dati…</span>
            </div>
            <div class="business-report-period">
                <label id="business-report-customer-type-label">Tipologia cliente</label>
                <div
                    class="business-report-select"
                    x-data="{ open: false, value: @js($customerType), options: @js($this->customerTypeOptions()) }"
                    x-on:click.outside="open = false"
                    x-on:keydown.escape.window="open = false"
                >
                    <button
                        id="business-report-customer-type"
                        class="business-report-select-trigger"
                        type="button"
                        aria-labelledby="business-report-customer-type-label business-report-customer-type"
                        x-bind:aria-expanded="open"
                        x-on:click="open = ! open"
                    >
                        <span x-text="options[value]"></span>
                        <x-heroicon-m-chevron-down x-bind:class="{ 'is-open': open }" />
                    </button>
                    <div class="business-report-select-menu" role="listbox" x-cloak x-show="open" x-transition.origin.top>
                        @foreach ($this->customerTypeOptions() as $value => $label)
                            <button
                                class="business-report-select-option"
                                type="button"
                                role="option"
                                x-bind:class="{ 'is-selected': value === @js($value) }"
                                x-bind:aria-selected="value === @js($value)"
                                x-on:click="value = @js($value); open = false; $wire.set('customerType', value)"
                            >
                                <span>{{ $label }}</span>
                                <x-heroicon-m-check />
                            </button>
                        @endforeach
                    </div>
                </div>
                <span class="business-report-loading" wire:loading wire:target="customerType">Aggiornamento dati…</span>
            </div>
        </section>

        <section class="business-report-cards" aria-label="Riepilogo economico">
            @foreach ($cards as [$key, $label, $value, $description, $icon, $tone])
                @php($trend = $trends[$key])
                <article class="business-report-card {{ $tone }}">
                    <span class="business-report-card-icon">
                        <x-dynamic-component :component="$icon" />
                    </span>
                    <div class="business-report-card-copy">
                        <span class="business-report-card-label">{{ $label }}</span>
                        <strong class="business-report-card-value">€ {{ number_format($value, 2, ',', '.') }}</strong>
                        <span class="business-report-card-description">{{ $description }}</span>
                        <span
                            class="business-report-trend {{ $trend['direction'] === 'flat' ? 'is-flat' : ($trend['favorable'] ? 'is-positive' : 'is-negative') }}"
                            title="{{ $trend['direction'] === 'flat' ? 'Andamento stabile' : ($trend['favorable'] ? 'Andamento favorevole' : 'Andamento sfavorevole') }}"
                            aria-label="{{ $trend['direction'] === 'flat' ? 'Andamento stabile' : ($trend['favorable'] ? 'Andamento favorevole' : 'Andamento sfavorevole') }}"
                        >
                            @if ($trend['direction'] === 'flat')
                                <x-heroicon-m-minus />
                            @elseif ($trend['favorable'])
                                <x-heroicon-m-arrow-trending-up />
                            @else
                                <x-heroicon-m-arrow-trending-down />
                            @endif
                        </span>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="business-report-grid">
            <section class="business-report-section is-wide is-tax">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-receipt-percent /></span>
                    <div><h2>Riepilogo IVA</h2><p>IVA sulle vendite, IVA compresa negli acquisti e relativo saldo, suddivisi per aliquota.</p></div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead><tr><th>Aliquota</th><th class="is-number">Vendite nette</th><th class="is-number">IVA vendite</th><th class="is-number">Acquisti netti</th><th class="is-number">IVA acquisti</th><th class="is-number">Saldo IVA</th><th class="is-number">Vendite IVA inclusa</th></tr></thead>
                        <tbody>
                            @forelse ($taxBreakdown as $row)
                                <tr><td class="is-name">IVA {{ number_format($row->tax_percentage, 2, ',', '.') }}%</td><td class="is-number">€ {{ number_format($row->taxable, 2, ',', '.') }}</td><td class="is-number"><strong>€ {{ number_format($row->tax, 2, ',', '.') }}</strong></td><td class="is-number">€ {{ number_format($row->purchase_taxable, 2, ',', '.') }}</td><td class="is-number">€ {{ number_format($row->purchase_tax, 2, ',', '.') }}</td><td class="is-number"><strong>€ {{ number_format($row->vat_balance, 2, ',', '.') }}</strong></td><td class="is-number">€ {{ number_format($row->gross, 2, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="7" class="business-report-empty">Nessun dato IVA nel periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="business-report-section is-wide is-product">
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

            <section class="business-report-section is-category">
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

            <section class="business-report-section is-recipient">
                <header class="business-report-section-heading">
                    <span class="business-report-section-icon"><x-heroicon-o-users /></span>
                    <div>
                        <h2>Forniture per destinatario</h2>
                        <p>Clienti e partner ordinati per valore delle forniture registrate.</p>
                    </div>
                </header>
                <div class="business-report-table-wrap">
                    <table class="business-report-table">
                        <thead>
                            <tr>
                                <th>Destinatario</th>
                                <th>Tipo</th>
                                <th class="is-number">Movimenti</th>
                                <th class="is-number">Ricavi</th>
                                <th class="is-number">Margine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $row)
                                <tr>
                                    <td class="is-name">{{ $row->display_name }}</td>
                                    <td><span class="business-report-source">{{ $row->recipient_type }}</span></td>
                                    <td class="is-number">{{ $row->orders_count }}</td>
                                    <td class="is-number">€ {{ number_format($row->revenue, 2, ',', '.') }}</td>
                                    <td class="is-number"><span class="business-report-margin">€ {{ number_format($row->margin, 2, ',', '.') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="business-report-empty">Nessuna fornitura disponibile nel periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
