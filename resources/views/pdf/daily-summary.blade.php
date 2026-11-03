<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Resoconto giornaliero</title>
    <style>
        @page { margin: 26px 30px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #173f38; font: 10px/1.4 DejaVu Sans, sans-serif; }
        .header { width: 100%; border-bottom: 3px solid #07845f; margin-bottom: 14px; }
        .header td { height: 78px; vertical-align: middle; }
        .title { text-align: right; }
        h1 { margin: 0; font-size: 20px; }
        .meta { color: #4a6b62; font-size: 10px; }
        .summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .summary td { width: 33.33%; padding: 10px 12px; border: 1px solid #d7e7e1; background: #f6fbf8; }
        .summary .label { display: block; margin-bottom: 3px; color: #4a6b62; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; }
        .summary .value { display: block; color: #07845f; font-size: 15px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 4px; page-break-inside: auto; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { padding: 6px 5px; color: white; background: #0d9c72; font-size: 8px; text-transform: uppercase; text-align: left; }
        .items td { padding: 6px 5px; border-bottom: 1px solid #d7e7e1; }
        .items tbody tr:nth-child(even) td { background: #f6fbf8; }
        .items tfoot td { padding: 7px 5px; border-top: 2px solid #07845f; font-weight: bold; }
        .right { text-align: right; }
        .footer { position: fixed; bottom: -26px; width: 100%; color: #718d86; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
<table class="header"><tr>
    <td>@if ($logo)<img src="{{ $logo['data'] }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" alt="Logo">@endif</td>
    <td class="title">
        <h1>RESOCONTO GIORNALIERO</h1>
        <div class="meta">
            {{ $date->translatedFormat('l d F Y') }}
            &middot; {{ $rows->count() }} {{ $rows->count() === 1 ? 'prodotto' : 'prodotti' }}
            &middot; {{ $includePartners ? 'clienti + partner' : 'solo clienti' }}
            &middot; generato il {{ $generatedAt->format('d/m/Y H:i') }}
        </div>
    </td>
</tr></table>

<table class="summary"><tr>
    <td><span class="label">Costo netto totale</span><span class="value">€ {{ number_format($totals['net'], 2, ',', '.') }}</span></td>
    <td><span class="label">IVA sugli acquisti</span><span class="value">€ {{ number_format($totals['tax'], 2, ',', '.') }}</span></td>
    <td><span class="label">Costo totale IVA inclusa</span><span class="value">€ {{ number_format($totals['gross'], 2, ',', '.') }}</span></td>
</tr></table>

<table class="items">
    <thead>
        <tr>
            <th>Prodotto</th>
            <th class="right">Quantità totale</th>
            <th class="right">Costo netto</th>
            <th class="right">IVA</th>
            <th class="right">Costo totale</th>
            <th>Origine</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>
            <td><strong>{{ $row->name }}</strong></td>
            <td class="right">{{ rtrim(rtrim(number_format($row->quantity, 3, ',', '.'), '0'), ',') }} {{ $row->symbol }}</td>
            <td class="right">€ {{ number_format($row->net, 2, ',', '.') }}</td>
            <td class="right">€ {{ number_format($row->tax, 2, ',', '.') }}</td>
            <td class="right">€ {{ number_format($row->gross, 2, ',', '.') }}</td>
            <td>{{ implode(' + ', $row->sources) }}</td>
        </tr>
    @empty
        <tr><td colspan="6">Nessuna bolla in questo giorno.</td></tr>
    @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2">Totale</td>
                <td class="right">€ {{ number_format($totals['net'], 2, ',', '.') }}</td>
                <td class="right">€ {{ number_format($totals['tax'], 2, ',', '.') }}</td>
                <td class="right">€ {{ number_format($totals['gross'], 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="footer">Il Paradiso della Frutta di Castaldo Mariarosaria &middot; P. IVA 02396610186 &middot; Resoconto generato il {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
