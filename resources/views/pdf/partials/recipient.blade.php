@php($recipient = $document->recipient_snapshot ?? [])
<div class="party">{{ $recipient['display_name'] ?? 'Destinatario non disponibile' }}</div>
@if (! empty($recipient['type']))<div>{{ $recipient['type'] }}</div>@endif
@if (! empty($recipient['company_name']) && $recipient['company_name'] !== ($recipient['display_name'] ?? null))
    <div>{{ $recipient['company_name'] }}</div>
@endif
@if (! empty($recipient['first_name']) || ! empty($recipient['last_name']))
    @php($person = trim(($recipient['first_name'] ?? '').' '.($recipient['last_name'] ?? '')))
    @if ($person !== ($recipient['display_name'] ?? null))<div>{{ $person }}</div>@endif
@endif
@if (! empty($recipient['address']))<div>{{ $recipient['address'] }}</div>@endif
@if (! empty($recipient['postal_code']) || ! empty($recipient['city']) || ! empty($recipient['province']))
    <div>{{ trim(($recipient['postal_code'] ?? '').' '.($recipient['city'] ?? '').(! empty($recipient['province']) ? ' ('.$recipient['province'].')' : '')) }}</div>
@endif
@if (! empty($recipient['vat_number']))<div>P. IVA {{ $recipient['vat_number'] }}</div>@endif
@if (! empty($recipient['tax_code']))<div>C.F. {{ $recipient['tax_code'] }}</div>@endif
@if (! empty($recipient['email']))<div>{{ $recipient['email'] }}</div>@endif
@if (! empty($recipient['phone']))<div>{{ $recipient['phone'] }}</div>@endif
