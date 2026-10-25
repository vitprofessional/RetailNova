@extends('include')
@section('backTitle'){{ __('mobile.ledger_'.$type) }}@endsection
@section('container')
@php
    $money = fn ($amount) => \App\Support\LocalizedNumber::money($amount);
    $datedTransactions = $transactions->groupBy(fn ($row) => $row['date'] ? \App\Support\MobileDateTime::date($row['date']) : __('mobile.ledger_undated'));
@endphp

@mobile
<main class="rn-m-detail-page rn-m-contact-ledger" data-contact-ledger="{{ $type }}">
    <header class="rn-m-list-header">
        @if($contact->image_thumbnail_url)<img src="{{ $contact->image_thumbnail_url }}" alt="" class="rn-m-ledger-avatar">@else<span class="rn-m-ledger-avatar rn-m-ledger-avatar--fallback" aria-hidden="true">{{ mb_substr($contact->name, 0, 1) }}</span>@endif
        <h2>{{ $contact->name }}</h2>
        @if($contact->mobile)<p class="rn-m-detail-contact">{{ $contact->mobile }}</p>@endif
    </header>
    <dl class="rn-m-summary rn-m-contact-ledger-summary">
        <div class="rn-m-summary-item"><dt>{{ __('mobile.ledger_total_'.$type) }}</dt><dd>{{ $money($summary['total']) }}</dd></div>
        <div class="rn-m-summary-item"><dt>{{ __('mobile.ledger_paid') }}</dt><dd>{{ $money($summary['paid']) }}</dd></div>
        <div class="rn-m-summary-item"><dt>{{ __('mobile.ledger_due') }}</dt><dd>{{ $money($summary['due']) }}</dd></div>
    </dl>
    <section class="rn-m-list-section" aria-label="{{ __('mobile.ledger_transactions') }}">
        <h2>{{ __('mobile.ledger_transactions') }}</h2>
        @if($transactions->isNotEmpty())
            @foreach($datedTransactions as $date => $rows)
                <h3 class="rn-m-date-heading">{{ $date }}</h3>
                <ul class="rn-m-cards" role="list">
                    @foreach($rows as $row)
                        <x-m.list-card :card="[
                        'id' => sha1($type.'|'.$row['reference'].'|'.(string)$row['date']),
                        'url' => $row['url'] ?? null,
                        'avatar' => mb_substr($row['reference'], 0, 1),
                        'title' => $row['reference'],
                        'subtitle' => $row['description'],
                        'amount' => $money($row['total']),
                        'meta' => array_values(array_filter([
                            __('mobile.ledger_paid').': '.$money($row['paid']),
                            $row['due'] > 0 ? __('mobile.ledger_due').': '.$money($row['due']) : null,
                        ])),
                        'badge' => null,
                        'actions' => [],
                        ]" />
                    @endforeach
                </ul>
            @endforeach
        @else
            <x-m.empty-state icon="ri-file-list-3-line" :title="__('mobile.ledger_empty')" />
        @endif
    </section>
</main>
@else
<div class="card rn-contact-ledger-desktop">
    <div class="card-header"><h2>{{ $contact->name }} · {{ __('mobile.ledger_'.$type) }}</h2></div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><strong>{{ __('mobile.ledger_total_'.$type) }}:</strong> {{ $money($summary['total']) }}</div>
            <div class="col-md-4"><strong>{{ __('mobile.ledger_paid') }}:</strong> {{ $money($summary['paid']) }}</div>
            <div class="col-md-4"><strong>{{ __('mobile.ledger_due') }}:</strong> {{ $money($summary['due']) }}</div>
        </div>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('common.reference') }}</th><th>{{ __('common.description') }}</th><th>{{ __('mobile.ledger_total_'.$type) }}</th><th>{{ __('mobile.ledger_paid') }}</th><th>{{ __('mobile.ledger_due') }}</th></tr></thead>
            <tbody>
            @forelse($transactions as $row)
                <tr><td>{{ $row['date'] ? \App\Support\MobileDateTime::date($row['date']) : '—' }}</td><td>{{ $row['reference'] }}</td><td>{{ $row['description'] }}</td><td>{{ $money($row['total']) }}</td><td>{{ $money($row['paid']) }}</td><td>{{ $money($row['due']) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center">{{ __('mobile.ledger_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endmobile
@endsection
