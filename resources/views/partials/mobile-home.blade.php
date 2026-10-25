{{-- Mobile home (mobile version only): today's figures, shortcuts, low-stock warning and the latest sales. --}}
@php
    $mobileNav = app(\App\Support\MobileNavigation::class);
    $money = fn ($amount) => \App\Support\LocalizedNumber::money($amount);
    $count = fn ($value) => \App\Support\LocalizedNumber::count($value);
    $stockUrl = $mobileNav->allows('stockProduct') ? route('stockProduct') : null;
@endphp
<section class="rn-m-home" aria-labelledby="rnHomeTitle" data-rn-home>
    <div class="rn-m-hero">
        <article class="rn-m-hero-card">
            <h2 id="rnHomeTitle" class="rn-m-hero-label">{{ __('mobile.today_sales') }}</h2>
            <p class="rn-m-hero-amount">{{ $money($today['sales']) }}</p>
            <p class="rn-m-hero-meta">{{ __('mobile.sales_today_count', ['count' => $count($today['count'])]) }}</p>
            <dl class="rn-m-stats">
                <div class="rn-m-stat rn-m-stat--cash"><dt>{{ __('mobile.cash_received') }}</dt><dd>{{ $money($today['cash']) }}</dd></div>
                <div class="rn-m-stat rn-m-stat--due"><dt>{{ __('mobile.today_due') }}</dt><dd>{{ $money($today['due']) }}</dd></div>
                <div class="rn-m-stat rn-m-stat--profit"><dt>{{ __('mobile.profit') }}</dt><dd>{{ $money($today['profit']) }}</dd></div>
            </dl>
        </article>
    </div>

    <div class="rn-m-home-section">
        <h2 class="sr-only">{{ __('mobile.shortcuts') }}</h2>
        <ul class="rn-m-grid" role="list">
            @foreach($mobileNav->shortcuts() as $item)
                <li><a href="{{ $item['url'] }}" class="rn-m-grid-item" title="{{ $item['label'] }}"><span class="rn-m-grid-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span><span class="rn-m-grid-label">{{ $item['label'] }}</span></a></li>
            @endforeach
        </ul>

        @if($today['low_stock_count'] > 0)
            @php $alertTag = $stockUrl ? 'a' : 'div'; @endphp
            <{{ $alertTag }} @if($stockUrl) href="{{ $stockUrl }}" @endif class="rn-m-alert" role="status">
                <i class="ri-alert-line" aria-hidden="true"></i>
                <span class="rn-m-alert-text">
                    <strong>{{ __('mobile.low_stock_alert', ['count' => $count($today['low_stock_count'])]) }}</strong>
                    <span>
                        @if($today['out_of_stock_count'] > 0){{ __('mobile.out_of_stock_alert', ['count' => $count($today['out_of_stock_count'])]) }} · @endif
                        {{ collect($today['low_stock'])->pluck('name')->implode(', ') }}
                    </span>
                </span>
                @if($stockUrl)<i class="ri-arrow-right-s-line" aria-hidden="true"></i>@endif
            </{{ $alertTag }}>
        @endif

        <section class="rn-m-card" aria-labelledby="rnRecentTitle">
            <h2 id="rnRecentTitle" class="rn-m-card-title">{{ __('mobile.recent_transactions') }}</h2>
            @if($today['recent'] === [])
                <p class="rn-m-empty">{{ __('mobile.no_transactions') }}</p>
            @else
                <ul class="p-0 m-0" role="list">
                    @foreach($today['recent'] as $sale)
                        <li class="rn-m-tx">
                            <span class="rn-m-tx-icon" aria-hidden="true"><i class="ri-shopping-cart-2-line"></i></span>
                            <span class="rn-m-tx-body">
                                <strong>{{ $sale['customer'] ?: __('mobile.walk_in_customer') }}</strong>
                                <span>@if(filled($sale['invoice'])){{ $sale['invoice'] }} · @endif{{ \App\Support\LocalizedNumber::digits(optional($sale['date'])->format('d/m/Y')) }}</span>
                            </span>
                            <span class="rn-m-tx-amount">{{ $money($sale['total']) }}@if($sale['due'] > 0)<small>{{ __('mobile.due') }} {{ $money($sale['due']) }}</small>@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</section>
