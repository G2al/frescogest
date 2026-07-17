<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_number }}</title>
    <style>
        @page { margin: 24px 28px 40px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #183f39; font-family: DejaVu Sans, sans-serif; font-size: 10.5px; line-height: 1.5; }
        .header { width: 100%; margin-bottom: 18px; table-layout: fixed; border-bottom: 3px solid #07845f; }
        .header td { height: 94px; padding-bottom: 13px; vertical-align: middle; }
        .logo-cell { width: 46%; padding-right: 20px; }
        .logo { display: block; }
        .brand { color: #07845f; font-size: 29px; font-weight: bold; }
        .document-title { width: 54%; text-align: right; }
        .document-title h1 { margin: 0; color: #073f3a; font-size: 23px; letter-spacing: .4px; }
        .document-title strong { display: block; margin: 5px 0 2px; color: #07845f; font-size: 13.5px; }
        .grid { width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 8px 0; }
        .grid td { width: 50%; padding: 12px; vertical-align: top; border: 1px solid #cfe3dc; border-radius: 6px; }
        .grid td:first-child { margin-left: -8px; }
        .label { margin-bottom: 5px; color: #07845f; font-size: 8.5px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .party-name { margin-bottom: 4px; color: #073f3a; font-size: 13.5px; font-weight: bold; }
        .meta { width: 100%; margin: 5px 0 15px; border-collapse: collapse; }
        .meta td { padding: 8px 10px; border: 1px solid #cfe3dc; }
        .meta .value { color: #073f3a; font-weight: bold; }
        .items { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .items thead { display: table-header-group; }
        .items th { padding: 9px 8px; color: #fff; background: #07845f; font-size: 8.5px; letter-spacing: .6px; text-align: left; text-transform: uppercase; }
        .items td { padding: 9px 8px; border-right: 1px solid #dcebe6; border-bottom: 1px solid #dcebe6; }
        .items td:first-child { border-left: 1px solid #dcebe6; }
        .items .number { text-align: right; }
        .transport { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .transport td { width: 25%; padding: 8px; vertical-align: top; border: 1px solid #dcebe6; }
        .notes { min-height: 48px; margin-bottom: 16px; padding: 10px; border: 1px solid #dcebe6; }
        .signatures { width: 100%; margin-top: 28px; border-collapse: separate; border-spacing: 12px 0; }
        .signatures td { width: 33.33%; height: 70px; padding-top: 8px; vertical-align: top; border-top: 1px solid #78968f; text-align: center; }
        .footer { position: fixed; right: 0; bottom: -27px; left: 0; padding-top: 7px; border-top: 1px solid #dcebe6; color: #66817b; font-size: 8px; text-align: center; }
        .muted { color: #66817b; }
    </style>
</head>
<body>
@php
    $sender = $document->sender_snapshot;
    $recipient = $document->recipient_snapshot;
    $destination = $document->destination_snapshot;
@endphp

<table class="header">
    <tr>
        <td class="logo-cell">
            @if ($logo)
                <img
                    class="logo"
                    src="{{ $logo['data'] }}"
                    width="{{ $logo['width'] }}"
                    height="{{ $logo['height'] }}"
                    alt="Frescogest"
                >
            @else
                <div class="brand">Frescogest</div>
            @endif
        </td>
        <td class="document-title">
            <h1>DOCUMENTO DI TRASPORTO</h1>
            <strong>{{ $document->document_number }}</strong>
            <span>Emesso il {{ $document->issued_at->format('d/m/Y \a\l\l\e H:i') }}</span>
        </td>
    </tr>
</table>

<table class="grid">
    <tr>
        <td>
            <div class="label">Cedente / Mittente</div>
            <div class="party-name">{{ $sender['business_name'] }}</div>
            <div>{{ $sender['address'] }}</div>
            <div>{{ $sender['postal_code'] }} {{ $sender['city'] }} {{ filled($sender['province'] ?? null) ? '('.$sender['province'].')' : '' }}</div>
            <div>P. IVA {{ $sender['vat_number'] }} @if(filled($sender['tax_code'] ?? null)) · C.F. {{ $sender['tax_code'] }} @endif</div>
            <div class="muted">{{ $sender['email'] ?? '' }} {{ filled($sender['phone'] ?? null) ? ' · '.$sender['phone'] : '' }}</div>
        </td>
        <td>
            <div class="label">Cessionario / Destinatario</div>
            <div class="party-name">{{ $recipient['display_name'] }}</div>
            <div>{{ $recipient['billing_address'] }}</div>
            <div>{{ $recipient['postal_code'] }} {{ $recipient['city'] }} {{ filled($recipient['province'] ?? null) ? '('.$recipient['province'].')' : '' }}</div>
            @if(filled($recipient['vat_number'] ?? null))<div>P. IVA {{ $recipient['vat_number'] }}</div>@endif
            @if(filled($recipient['tax_code'] ?? null))<div>C.F. {{ $recipient['tax_code'] }}</div>@endif
            <div class="muted">{{ $recipient['email'] ?? '' }} {{ filled($recipient['phone'] ?? null) ? ' · '.$recipient['phone'] : '' }}</div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><div class="label">Ordine</div><span class="value">{{ $order->order_number }}</span></td>
        <td><div class="label">Data ordine</div><span class="value">{{ $order->requested_at->format('d/m/Y') }}</span></td>
        <td><div class="label">Causale</div><span class="value">{{ $document->transport_reason }}</span></td>
        <td><div class="label">Pagamento</div><span class="value">Registrato il {{ $order->paid_at->format('d/m/Y') }}</span></td>
    </tr>
</table>

<div class="label">Luogo di destinazione</div>
<div class="notes">
    <strong>{{ $destination['address'] ?: 'Non specificato' }}</strong><br>
    {{ $destination['postal_code'] }} {{ $destination['city'] }} {{ filled($destination['province'] ?? null) ? '('.$destination['province'].')' : '' }}
    @if(filled($destination['notes'] ?? null))<br><span class="muted">{{ $destination['notes'] }}</span>@endif
</div>

<table class="items">
    <thead>
        <tr><th style="width: 14%">Codice</th><th>Descrizione dei beni</th><th style="width: 15%">Unità</th><th style="width: 15%; text-align: right">Quantità</th></tr>
    </thead>
    <tbody>
        @foreach ($document->items_snapshot as $item)
            <tr>
                <td>{{ $item['code'] ?: '—' }}</td>
                <td><strong>{{ $item['name'] }}</strong></td>
                <td>{{ $item['unit_name'] }} ({{ $item['unit_symbol'] }})</td>
                <td class="number">{{ number_format((float) $item['quantity'], 3, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="transport">
    <tr>
        <td><div class="label">Trasporto a cura</div><strong>{{ $document->transport_method }}</strong></td>
        <td><div class="label">Inizio trasporto</div><strong>{{ $document->transport_started_at?->format('d/m/Y H:i') ?: '—' }}</strong></td>
        <td><div class="label">Aspetto beni</div><strong>{{ $document->goods_appearance ?: '—' }}</strong></td>
        <td><div class="label">Colli / Peso</div><strong>{{ $document->packages_count ?: '—' }} / {{ $document->total_weight ? number_format((float) $document->total_weight, 3, ',', '.').' kg' : '—' }}</strong></td>
    </tr>
    <tr>
        <td colspan="2"><div class="label">Vettore</div><strong>{{ $document->carrier_name ?: 'Non indicato' }}</strong><br>{{ $document->carrier_vat_number ? 'P. IVA '.$document->carrier_vat_number : '' }} {{ $document->carrier_tax_code ? ' · C.F. '.$document->carrier_tax_code : '' }}</td>
        <td colspan="2"><div class="label">Targa mezzo</div><strong>{{ $document->vehicle_registration ?: '—' }}</strong></td>
    </tr>
</table>

@if(filled($document->notes))
    <div class="label">Annotazioni</div>
    <div class="notes">{{ $document->notes }}</div>
@endif

<table class="signatures">
    <tr><td>Firma del cedente</td><td>Firma del vettore</td><td>Firma del destinatario</td></tr>
</table>

<div class="footer">{{ $sender['business_name'] }} · P. IVA {{ $sender['vat_number'] }} · Documento {{ $document->document_number }}</div>
</body>
</html>
