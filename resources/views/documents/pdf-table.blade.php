<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: hind; color: #000; font-size: {{ $paper === 'thermal58' ? '8' : ($paper === 'thermal80' ? '9' : '10') }}pt; }
        table { width: 100%; border-collapse: collapse; }
        td, th { color: #000; }
        .brand { text-align: {{ $paper === 'a4' ? 'left' : 'center' }}; margin-bottom: 4mm; }
        .logo { display: block; width: auto; height: auto; max-width: {{ $paper === 'a4' ? '36' : '24' }}mm; max-height: 15mm; margin: 0 auto 2mm; }
        .shop { font-size: {{ $paper === 'a4' ? '16' : ($paper === 'thermal58' ? '11' : '12') }}pt; font-weight: bold; }
        .contact { font-size: {{ $paper === 'a4' ? '9' : ($paper === 'thermal58' ? '7' : '8') }}pt; }
        .doc-title { text-align: center; margin: 2mm 0; font-size: {{ $paper === 'a4' ? '12' : '9' }}pt; font-weight: bold; }
        .doc-meta { text-align: center; font-size: {{ $paper === 'a4' ? '9' : ($paper === 'thermal58' ? '7' : '8') }}pt; margin-bottom: 3mm; }
        .party { border-top: .2mm solid #aaa; border-bottom: .2mm solid #aaa; padding: 2mm 0; margin-bottom: 3mm; }
        .party-name { font-weight: bold; }
        .a4-items th, .a4-items td { padding: 1.5mm 1mm; border-bottom: .2mm solid #aaa; }
        .a4-items th { text-align: left; font-weight: bold; }
        .a4-items .num { text-align: right; }
        .thermal-item { border-bottom: .2mm solid #ccc; padding: 1.2mm 0; }
        .thermal-name { font-size: {{ $paper === 'thermal58' ? '8' : '9' }}pt; font-weight: bold; width: 100%; }
        .thermal-detail { width: 100%; font-size: {{ $paper === 'thermal58' ? '7' : '8' }}pt; }
        .thermal-total { text-align: right; font-size: {{ $paper === 'thermal58' ? '8' : '9' }}pt; font-weight: bold; }
        .serial { font-size: {{ $paper === 'thermal58' ? '7' : '8' }}pt; }
        .totals { margin-top: 3mm; }
        .totals td { padding: 1mm 0; }
        .totals .amount { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .totals .grand td { font-size: {{ $paper === 'a4' ? '13' : ($paper === 'thermal58' ? '10' : '11') }}pt; font-weight: bold; border-top: .4mm solid #555; border-bottom: .4mm solid #555; padding: 2mm 0; }
        .footer { margin-top: 4mm; border-top: .2mm solid #aaa; padding-top: 2mm; text-align: center; font-size: {{ $paper === 'a4' ? '9' : ($paper === 'thermal58' ? '7' : '8') }}pt; }
        .ledger th, .ledger td { font-size: {{ $paper === 'a4' ? '8' : ($paper === 'thermal58' ? '7' : '8') }}pt; padding: 1mm .5mm; border-bottom: .2mm solid #ccc; }
        .ledger th { text-align: left; }
        .ledger .num { text-align: right; white-space: nowrap; }
        .current-balance { margin-top: 4mm; padding: 2mm 0; border-top: .5mm solid #333; border-bottom: .5mm solid #333; font-size: {{ $paper === 'a4' ? '13' : ($paper === 'thermal58' ? '10' : '11') }}pt; font-weight: bold; }
    </style>
</head>
<body>
<div class="brand">
    @if($logo)<img class="logo" src="{{ $logo }}" alt="">@endif
    <div class="shop">{{ $business?->businessName ?: config('app.name') }}</div>
    @if($business?->businessLocation)<div class="contact">{{ $business->businessLocation }}</div>@endif
    @if($business?->mobile)<div class="contact">{{ __('common.phone') }}: {{ $business->mobile }}</div>@endif
    @if($business?->email)<div class="contact">{{ __('common.email') }}: {{ $business->email }}</div>@endif
</div>
<div class="doc-title">{{ $receipt['document_label'] }}</div>
<div class="doc-meta">{{ $receipt['document_number'] }} · {{ $receipt['status'] }}</div>
<div class="party"><span>{{ $receipt['party_label'] }}:</span> <span class="party-name">{{ $receipt['party_name'] }}</span>@if(!empty($receipt['party_meta']))<br>{{ $receipt['party_meta'] }}@endif</div>

@if($templateType === 'ledger')
    <table class="ledger">
        <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('common.description') }}</th><th>{{ __('documents.sale_purchase') }}</th><th>{{ __('documents.deposit') }}</th><th>{{ __('documents.balance') }}</th></tr></thead>
        <tbody>@foreach($receipt['ledger_rows'] ?? [] as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['description'] }}</td><td class="num">{{ $row['sale_purchase'] }}</td><td class="num">{{ $row['deposit'] }}</td><td class="num">{{ $row['balance'] }}</td></tr>@endforeach</tbody>
    </table>
    <div class="current-balance">{{ __('documents.current_balance') }}: {{ $receipt['current_balance'] }}</div>
@elseif($paper === 'a4')
    <table class="a4-items">
        <colgroup><col style="width:50%"><col style="width:12%"><col style="width:18%"><col style="width:20%"></colgroup>
        <thead><tr><th>{{ __('documents.product') }}</th><th class="num">{{ __('documents.quantity') }}</th><th class="num">{{ __('documents.rate') }}</th><th class="num">{{ __('documents.amount') }}</th></tr></thead>
        <tbody>@forelse($receipt['items'] ?? [] as $line)<tr><td>{{ $line['title'] }}</td><td class="num">{{ $line['quantity'] }}</td><td class="num">{{ $line['rate'] }}</td><td class="num"><strong>{{ $line['amount'] }}</strong></td></tr>@empty<tr><td colspan="4">{{ $receipt['empty_label'] }}</td></tr>@endforelse</tbody>
    </table>
@else
    @forelse($receipt['items'] ?? [] as $line)
        <table class="thermal-item"><tbody>
            <tr><td colspan="2" class="thermal-name">{{ $line['title'] }}</td></tr>
            <tr><td class="thermal-detail">{{ $line['quantity'] }} × {{ $line['rate'] }}</td><td class="thermal-total">{{ $line['amount'] }}</td></tr>
            @if(!empty($line['serials']))<tr><td colspan="2" class="serial">{{ __('common.s_n') }}: {{ implode(', ', $line['serials']) }}</td></tr>@endif
        </tbody></table>
    @empty<div>{{ $receipt['empty_label'] }}</div>@endforelse
@endif

@if($templateType !== 'ledger')
    <table class="totals"><tbody>@foreach($receipt['totals'] ?? [] as $total)<tr @if(!empty($total['is_total']))class="grand"@endif><td>{{ $total['label'] }}</td><td class="amount">{{ $total['amount'] }}</td></tr>@endforeach</tbody></table>
    <div class="footer">{{ $business?->invoiceFooter ?: __('common.thank_you_for_your_business') }}</div>
@endif
</body>
</html>
