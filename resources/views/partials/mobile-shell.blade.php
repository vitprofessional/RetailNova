{{-- Mobile app shell (mobile version only, see App\Support\MobileView): header, bottom tabs and the "More" page. --}}
@auth('admin')
@php
    $mobileNav = app(\App\Support\MobileNavigation::class);
    $mobileTabs = collect($mobileNav->tabs())->keyBy('key');
    $mobileTitle = trim(strip_tags($__env->yieldContent('backTitle'))) ?: ($rnBusinessName ?? config('app.name'));
    $mobileIsHome = request()->routeIs('dashboard');
    $mobileUser = auth('admin')->user();
    $mobileLocale = app()->getLocale();
    if ($mobileIsHome) {
        $mobileBusinessName = $rnBusinessName ?? config('app.name');
        $mobileBusiness = ($config ?? collect())->first();
        $mobileLogo = $mobileBusiness instanceof \App\Models\BusinessSetup ? $mobileBusiness->invoiceLogoUrl() : null;
        $mobileInitial = preg_match('/\p{L}/u', (string) $mobileBusinessName, $letter) ? mb_strtoupper($letter[0]) : '#';
        $mobileDate = \App\Support\LocalizedNumber::digits(now()->locale($mobileLocale)->translatedFormat('l, j F Y'));
        // Alerts come from the home figures (DashboardToday); only stock warnings exist so far.
        $mobileAlerts = [];
        if (($today['low_stock_count'] ?? 0) > 0) {
            $mobileAlerts[] = [
                'icon' => 'ri-alert-line',
                'title' => __('mobile.low_stock_alert', ['count' => \App\Support\LocalizedNumber::count($today['low_stock_count'])]),
                'text' => collect($today['low_stock'])->pluck('name')->implode(', '),
                'url' => $mobileNav->allows('stockProduct') ? route('stockProduct') : null,
            ];
        }
        if (($today['out_of_stock_count'] ?? 0) > 0) {
            $mobileAlerts[] = [
                'icon' => 'ri-error-warning-line',
                'title' => __('mobile.out_of_stock_alert', ['count' => \App\Support\LocalizedNumber::count($today['out_of_stock_count'])]),
                'text' => null,
                'url' => $mobileNav->allows('stockProduct') ? route('stockProduct') : null,
            ];
        }
    }
@endphp
<header class="rn-m-header{{ $mobileIsHome ? ' rn-m-header--home' : '' }}" data-rn-mobile-header>
    @if($mobileIsHome)
        <span class="rn-m-brand" aria-hidden="true">
            @if($mobileLogo)<img src="{{ $mobileLogo }}" alt="" class="rn-m-brand-logo">@else{{ $mobileInitial }}@endif
        </span>
        <div class="rn-m-header-text">
            <h1 class="rn-m-title">{{ $mobileBusinessName }}</h1>
            <p class="rn-m-header-date" data-rn-header-date>{{ $mobileDate }}</p>
        </div>
        <button type="button" class="rn-m-header-btn rn-m-bell" data-rn-notifications-toggle aria-expanded="false" aria-controls="rnNotifications"
                aria-label="{{ __('mobile.notifications') }}{{ $mobileAlerts ? ' ('.\App\Support\LocalizedNumber::count(count($mobileAlerts)).')' : '' }}">
            <i class="ri-notification-3-line" aria-hidden="true"></i>
            @if($mobileAlerts)<span class="rn-m-bell-badge" aria-hidden="true">{{ \App\Support\LocalizedNumber::count(count($mobileAlerts)) }}</span>@endif
        </button>
    @else
        <a href="{{ route('dashboard') }}" class="rn-m-header-btn" data-rn-back aria-label="{{ __('mobile.back') }}"><i class="ri-arrow-left-line" aria-hidden="true"></i></a>
        <h1 class="rn-m-title">{{ $mobileTitle }}</h1>
    @endif
</header>
@if($mobileIsHome)
    <section id="rnNotifications" class="rn-m-notifications" aria-labelledby="rnNotificationsTitle" data-rn-notifications hidden>
        <h2 id="rnNotificationsTitle" class="rn-m-notifications-title">{{ __('mobile.notifications') }}</h2>
        @forelse($mobileAlerts as $alert)
            @php $alertTag = $alert['url'] ? 'a' : 'div'; @endphp
            <{{ $alertTag }} @if($alert['url']) href="{{ $alert['url'] }}" @endif class="rn-m-notification">
                <i class="{{ $alert['icon'] }}" aria-hidden="true"></i>
                <span><strong>{{ $alert['title'] }}</strong>@if($alert['text'])<span>{{ $alert['text'] }}</span>@endif</span>
            </{{ $alertTag }}>
        @empty
            <p class="rn-m-empty">{{ __('mobile.no_notifications') }}</p>
        @endforelse
    </section>
@endif

<nav class="rn-m-tabbar" aria-label="{{ __('mobile.navigation') }}" data-rn-tabbar>
    @foreach(['home', 'stock', 'sale', 'reports'] as $key)
        @if($tab = $mobileTabs->get($key))
            <a href="{{ $tab['url'] }}" class="rn-m-tab{{ $key === 'sale' ? ' rn-m-tab--sale' : '' }}{{ $tab['active'] ? ' is-active' : '' }}" data-rn-tab="{{ $key }}" @if($tab['active']) aria-current="page" @endif>
                <span class="rn-m-tab-icon" aria-hidden="true"><i class="{{ $tab['icon'] }}"></i></span>
                <span class="rn-m-tab-label">{{ $tab['label'] }}</span>
            </a>
        @endif
    @endforeach
    <a href="#rn-more" class="rn-m-tab" data-rn-tab="more" aria-controls="rn-more">
        <span class="rn-m-tab-icon" aria-hidden="true"><i class="ri-apps-2-line"></i></span>
        <span class="rn-m-tab-label">{{ __('mobile.more') }}</span>
    </a>
</nav>

<section id="rn-more" class="rn-m-more" role="dialog" aria-modal="true" aria-labelledby="rn-more-title" data-rn-more>
    <header class="rn-m-header rn-m-more-header">
        <a href="#rn-more-closed" class="rn-m-header-btn" data-rn-more-close aria-label="{{ __('mobile.close') }}"><i class="ri-close-line" aria-hidden="true"></i></a>
        <h2 id="rn-more-title" class="rn-m-title">{{ __('mobile.more') }}</h2>
    </header>
    <div class="rn-m-more-body">
        @foreach($mobileNav->moreSections() as $section)
            <section class="rn-m-more-section" aria-labelledby="rnMore-{{ $section['key'] }}" data-rn-more-section="{{ $section['key'] }}">
                <h3 id="rnMore-{{ $section['key'] }}" class="rn-m-section-title">{{ $section['title'] }}</h3>
                <ul class="rn-m-grid" role="list">
                    @foreach($section['items'] as $item)
                        <li><a href="{{ $item['url'] }}" class="rn-m-grid-item"><span class="rn-m-grid-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span><span class="rn-m-grid-label">{{ $item['label'] }}</span></a></li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <h3 class="rn-m-section-title">{{ __('mobile.language') }}</h3>
        <form method="POST" action="{{ route('language.preference') }}" class="rn-m-segment" data-rn-language>
            @csrf
            <button type="submit" name="preferred_locale" value="bn" class="rn-m-segment-btn{{ $mobileLocale === 'bn' ? ' is-active' : '' }}" @if($mobileLocale === 'bn') aria-pressed="true" @endif lang="bn">বাংলা</button>
            <button type="submit" name="preferred_locale" value="en" class="rn-m-segment-btn{{ $mobileLocale === 'en' ? ' is-active' : '' }}" @if($mobileLocale === 'en') aria-pressed="true" @endif lang="en">English</button>
        </form>

        <h3 class="rn-m-section-title">{{ __('mobile.view_version') }}</h3>
        <form method="POST" action="{{ route('view.mode') }}" class="rn-m-segment" data-rn-view-mode>
            @csrf
            <button type="submit" name="mode" value="mobile" class="rn-m-segment-btn is-active" aria-pressed="true">{{ __('mobile.view_mobile') }}</button>
            <button type="submit" name="mode" value="desktop" class="rn-m-segment-btn">{{ __('mobile.view_desktop') }}</button>
        </form>

        <a href="{{ route('logout') }}" class="rn-m-signout"><i class="ri-logout-box-r-line" aria-hidden="true"></i><span>{{ __('mobile.sign_out') }}</span></a>
        @if($mobileUser)
            <p class="rn-m-signed-in">{{ $mobileUser->fullName }}</p>
        @endif
    </div>
</section>
@endauth
