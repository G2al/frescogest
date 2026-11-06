<x-filament-panels::page>
    @php
        $rows = $this->rows();
        $totals = $this->totals();
        $money = fn ($value) => '€ '.number_format((float) $value, 2, ',', '.');
        $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp

    <div class="business-report">
        <section class="business-report-toolbar">
            <div class="business-report-toolbar-copy">
                <span class="business-report-toolbar-icon"><x-heroicon-o-clipboard-document-list /></span>
                <div>
                    <h2>Giorno</h2>
                    <p>Somma tutti gli articoli di tutte le bolle del giorno, raggruppati per prodotto.</p>
                </div>
            </div>
            <div class="business-report-period">
                <label for="daily-summary-date">Data</label>
                <input id="daily-summary-date" type="date" wire:model.live="date">
            </div>
            <div class="business-report-period">
                <label for="daily-summary-partners">Bolle partner</label>
                <select id="daily-summary-partners" wire:model.live="includePartners">
                    <option value="1">Incluse</option>
                    <option value="0">Escluse</option>
                </select>
            </div>
        </section>

        <section class="business-report-cards">
            <article class="business-report-card is-blue">
                <span class="business-report-card-icon"><x-heroicon-o-cube /></span>
                <div class="business-report-card-copy">
                    <span class="business-report-card-label">Prodotti diversi</span>
                    <strong class="business-report-card-value">{{ $rows->count() }}</strong>
                    <span class="business-report-card-description">Articoli distinti nel giorno</span>
                </div>
            </article>
            <article class="business-report-card is-green">
                <span class="business-report-card-icon"><x-heroicon-o-banknotes /></span>
                <div class="business-report-card-copy">
                    <span class="business-report-card-label">Costo netto totale</span>
                    <strong class="business-report-card-value">{{ $money($totals['net']) }}</strong>
                    <span class="business-report-card-description">Quanto hai speso, somma di tutte le righe</span>
                </div>
            </article>
            <article class="business-report-card is-violet">
                <span class="business-report-card-icon"><x-heroicon-o-receipt-percent /></span>
                <div class="business-report-card-copy">
                    <span class="business-report-card-label">Costo IVA inclusa</span>
                    <strong class="business-report-card-value">{{ $money($totals['gross']) }}</strong>
                    <span class="business-report-card-description">IVA sugli acquisti: {{ $money($totals['tax']) }}</span>
                </div>
            </article>
        </section>

        <div class="business-report-grid">
            <section class="business-report-section is-wide is-product" x-data="{ open: false }">
                <button type="button" class="business-report-section-heading is-toggle" x-on:click="open = ! open" x-bind:aria-expanded="open">
                    <span class="business-report-section-icon"><x-heroicon-o-clipboard-document-list /></span>
                    <span class="business-report-section-heading-text">
                        <h2>Elenco complessivo del giorno</h2>
                        <p>Quantità totale da preparare e costo di acquisto per ogni prodotto. Clicca per aprire.</p>
                    </span>
                    <x-heroicon-m-chevron-down class="business-report-section-toggle-icon" x-bind:class="{ 'is-open': open }" />
                </button>
                <div x-show="open" x-collapse x-cloak>
                    <div class="business-report-table-wrap">
                        <table class="business-report-table">
                            <thead>
                                <tr>
                                    <th>Prodotto</th>
                                    <th class="is-number">Quantità totale</th>
                                    <th class="is-number">Prezzo unitario</th>
                                    <th class="is-number">Costo netto</th>
                                    <th class="is-number">IVA</th>
                                    <th class="is-number">Costo totale</th>
                                    <th>Origine</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php $isManual = $row->is_manual ?? false; @endphp
                                    <tr @class(['is-manual-override' => $isManual])>
                                        <td class="is-name">{{ $row->name }}</td>
                                        <td class="is-number">{{ $quantity($row->quantity) }} {{ $row->symbol }}</td>
                                        <td class="is-number">{{ $row->quantity > 0 ? $money($row->net / $row->quantity).' / '.$row->symbol : '—' }}</td>
                                        <td class="is-number">{{ $money($row->net) }}</td>
                                        <td class="is-number">{{ $money($row->tax) }}</td>
                                        <td class="is-number"><strong>{{ $money($row->gross) }}</strong></td>
                                        <td>
                                            @if ($isManual)
                                                <span class="business-report-badge is-manual">Modificato manualmente</span>
                                            @else
                                                {{ implode(' + ', $row->sources) }}
                                            @endif
                                        </td>
                                        <td class="is-actions">
                                            @if ($row->product_id)
                                                <button type="button" class="business-report-link-action"
                                                    wire:click="mountAction('editRow', {{ json_encode([
                                                        'productId' => $row->product_id,
                                                        'quantity' => $row->quantity,
                                                        'net' => $row->net,
                                                        'tax' => $row->tax,
                                                        'gross' => $row->gross,
                                                    ]) }})">Modifica</button>
                                            @endif
                                            @if ($isManual)
                                                <button type="button" class="business-report-link-action is-danger"
                                                    wire:click="mountAction('deleteOverride', {{ json_encode(['overrideId' => $row->override_id]) }})">Annulla</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="business-report-empty">Nessuna bolla in questo giorno.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
