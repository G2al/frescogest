<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Catalogo prodotti</title>
    <style>
        @page { margin: 26px 30px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #173f38; font: 10px/1.4 DejaVu Sans, sans-serif; }
        .header { width: 100%; border-bottom: 3px solid #07845f; margin-bottom: 14px; }
        .header td { height: 78px; vertical-align: middle; }
        .title { text-align: right; }
        h1 { margin: 0; font-size: 20px; }
        .meta { color: #4a6b62; font-size: 10px; }
        .category-title { margin: 16px 0 6px; padding: 7px 9px; color: #fff; background: #07845f; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 4px; page-break-inside: auto; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { padding: 6px 5px; color: white; background: #0d9c72; font-size: 8px; text-transform: uppercase; text-align: left; }
        .items td { padding: 6px 5px; border-bottom: 1px solid #d7e7e1; }
        .items tbody tr:nth-child(even) td { background: #f6fbf8; }
        .right { text-align: right; }
        .fill-in { position: relative; }
        .fill-in::after { content: ""; display: block; height: 0; border-bottom: 1px dashed #a9c4bb; }
        .status-inactive { color: #b23b3b; font-weight: bold; }
        .footer { position: fixed; bottom: -26px; width: 100%; color: #718d86; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
<table class="header"><tr>
    <td>@if ($logo)<img src="{{ $logo['data'] }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" alt="Logo">@endif</td>
    <td class="title">
        <h1>CATALOGO PRODOTTI</h1>
        <div class="meta">{{ $totalProducts }} {{ $totalProducts === 1 ? 'prodotto' : 'prodotti' }} · generato il {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </td>
</tr></table>

@foreach ($groups as $group)
    <div class="category-title">{{ $group['category']->name }} &middot; {{ $group['products']->count() }} {{ $group['products']->count() === 1 ? 'prodotto' : 'prodotti' }}</div>
    <table class="items">
        <thead>
            <tr>
                <th>Prodotto</th>
                <th>Codice</th>
                <th>Unità</th>
                <th class="right">Costo netto</th>
                <th class="right">Prezzo privati</th>
                <th class="right">Prezzo ristoratori</th>
                <th class="right">Prezzo partner</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($group['products'] as $product)
            <tr>
                <td><strong>{{ $product->name }}</strong></td>
                <td>{{ $product->code }}</td>
                <td>{{ $product->defaultUnitOfMeasure?->symbol }}</td>
                <td class="right fill-in"></td>
                <td class="right fill-in"></td>
                <td class="right fill-in"></td>
                <td class="right fill-in"></td>
                <td class="{{ $product->active ? '' : 'status-inactive' }}">{{ $product->active ? 'Attivo' : 'Non attivo' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

<div class="footer">Il Paradiso della Frutta di Castaldo Mariarosaria &middot; P. IVA 02396610186 &middot; Catalogo generato il {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
