<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_number }}</title>
    <style>
        @page { margin: 28px 34px 45px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #173f38; font: 11px/1.45 DejaVu Sans, sans-serif; }
        .header { width: 100%; border-bottom: 3px solid #07845f; margin-bottom: 18px; }
        .header td { height: 95px; vertical-align: middle; }
        .title { text-align: right; }
        h1 { margin: 0; font-size: 23px; }
        .number { color: #07845f; font-weight: bold; font-size: 14px; }
        .parties { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .parties td { width: 50%; border: 1px solid #cde1da; padding: 12px; vertical-align: top; }
        .payment { margin: -7px 0 14px; padding: 8px 10px; border: 1px solid #d7e7e1; background: #f6fbf8; }
        .label { color: #07845f; font-size: 9px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .party { margin-top: 6px; font-size: 14px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th { padding: 8px 6px; color: white; background: #07845f; font-size: 8px; text-transform: uppercase; }
        .items td { padding: 8px 6px; border: 1px solid #d7e7e1; }
        .right { text-align: right; }
        .totals { width: 45%; margin: 14px 0 0 auto; border-collapse: collapse; }
        .totals td { padding: 7px 9px; border: 1px solid #d7e7e1; }
        .totals tr:last-child { font-size: 13px; font-weight: bold; background: #eef8f4; }
        .signatures { width: 100%; margin-top: 70px; border-spacing: 30px 0; }
        .signatures td { width: 50%; padding-top: 8px; border-top: 1px solid #718d86; text-align: center; }
        .footer { position: fixed; bottom: -25px; width: 100%; color: #718d86; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
@php($sender = $document->sender_snapshot)
<table class="header"><tr>
    <td>
        @if ($logo)<img src="{{ $logo['data'] }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" alt="Logo">@endif
    </td>
    <td class="title"><h1>BOLLA DI CONSEGNA</h1><div class="number">{{ $document->document_number }}</div><div>{{ $document->issued_at->format('d/m/Y H:i') }}</div></td>
</tr></table>

<table class="parties"><tr>
    <td><div class="label">Cedente</div><div class="party">Il Paradiso della Frutta</div><div>di Castaldo Mariarosaria</div><div>Via dei Caduti Genovesi, 8</div><div>Bornasco (PV)</div><div>P. IVA 02396610186</div></td>
    <td><div class="label">Ricevente</div><div class="party">{{ $document->recipient_snapshot['display_name'] }}</div></td>
</tr></table>

<div class="payment"><strong>Pagamento:</strong> {{ $document->payment_method_snapshot ?: 'Da concordare' }}</div>

<table class="items">
    <thead><tr><th>Prodotto</th><th>Quantità</th><th class="right">Prezzo netto</th><th class="right">IVA</th><th class="right">Totale netto</th><th class="right">Totale IVA incl.</th></tr></thead>
    <tbody>@foreach ($document->items_snapshot as $item)<tr>
        <td><strong>{{ $item['name'] }}</strong>@if ((float) ($item['discount_percentage'] ?? 0) > 0)<br><span class="label">Sconto {{ number_format((float) $item['discount_percentage'], 2, ',', '.') }}% (-€ {{ number_format((float) $item['discount_amount_net'], 2, ',', '.') }})</span>@endif</td>
        <td>{{ rtrim(rtrim(number_format((float) $item['quantity'], 3, ',', '.'), '0'), ',') }} {{ $item['unit_symbol'] }}</td>
        <td class="right">€ {{ number_format((float) $item['unit_price_net'], 2, ',', '.') }}/{{ $item['unit_symbol'] }}</td>
        <td class="right">{{ number_format((float) $item['tax_percentage'], 2, ',', '.') }}%</td>
        <td class="right">€ {{ number_format((float) $item['line_net'], 2, ',', '.') }}</td>
        <td class="right">€ {{ number_format((float) $item['line_gross'], 2, ',', '.') }}</td>
    </tr>@endforeach</tbody>
</table>

<table class="totals">
    <tr><td>Subtotale netto</td><td class="right">€ {{ number_format((float) $document->subtotal_net, 2, ',', '.') }}</td></tr>
    @if ((float) $document->discount_amount_net > 0)<tr><td>Sconto {{ number_format((float) $document->discount_percentage, 2, ',', '.') }}%</td><td class="right">- € {{ number_format((float) $document->discount_amount_net, 2, ',', '.') }}</td></tr>@endif
    @if ((float) $document->shipping_amount_net > 0)<tr><td>Consegna netta</td><td class="right">€ {{ number_format((float) $document->shipping_amount_net, 2, ',', '.') }}</td></tr>@endif
    <tr><td>Totale netto</td><td class="right">€ {{ number_format((float) $document->total_net, 2, ',', '.') }}</td></tr>
    <tr><td>IVA</td><td class="right">€ {{ number_format((float) $document->total_tax, 2, ',', '.') }}</td></tr>
    <tr><td>Totale IVA inclusa</td><td class="right">€ {{ number_format((float) $document->total_gross, 2, ',', '.') }}</td></tr>
</table>

<table class="signatures"><tr><td>Il cedente</td><td>Firma del ricevente</td></tr></table>
<div class="footer">Il Paradiso della Frutta di Castaldo Mariarosaria · P. IVA 02396610186 · {{ $document->document_number }}</div>
</body>
</html>
