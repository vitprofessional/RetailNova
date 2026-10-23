{{-- Mobile app shell (≤1023px only; hidden on desktop by rn-mobile.css): header, bottom tabs and the "More" page. --}}
@auth('admin')
@php
    $mobileNav = app(\App\Support\MobileNavigation::class);
    $mobileTabs = collect($mobileNav->tabs())->keyBy('key');
    $mobileTitle = trim(strip_tags($__env->yieldContent('backTitle'))) ?: ($rnBusinessName ?? config('app.name'));
    $mobileIsHome = request()->routeIs('dashboard');
    $mobileUser = auth('admin')->user();
    $mobileLocale = app()->getLocale();
@endphp
<header class="rn-m-header{{ $mobileIsHome ? ' rn-m-header--home' : '' }}" data-rn-mobile-header>
    @unless($mobileIsHome)
        <a href="{{ route('dashboard') }}" class="rn-m-header-btn" data-rn-back aria-label="{{ __('mobile.back') }}"><i class="ri-arrow-left-line" aria-hidden="true"></i></a>
    @endunless
    <h1 class="rn-m-title">{{ $mobileIsHome ? ($rnBusinessName ?? config('app.name')) : $mobileTitle }}</h1>
</header>

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
        <ul class="rn-m-grid" role="list">
            @foreach($mobileNav->moreItems() as $item)
                <li><a href="{{ $item['url'] }}" class="rn-m-grid-item"><span class="rn-m-grid-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span><span class="rn-m-grid-label">{{ $item['label'] }}</span></a></li>
            @endforeach
        </ul>

        @if($settings = $mobileNav->settingsItems())
            <h3 class="rn-m-section-title">{{ __('mobile.settings') }}</h3>
            <ul class="rn-m-list" role="list">
                @foreach($settings as $item)
                    <li><a href="{{ $item['url'] }}" class="rn-m-list-item"><i class="{{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span><i class="ri-arrow-right-s-line rn-m-list-chevron" aria-hidden="true"></i></a></li>
                @endforeach
            </ul>
        @endif

        <h3 class="rn-m-section-title">{{ __('mobile.language') }}</h3>
        <form method="POST" action="{{ route('language.preference') }}" class="rn-m-segment" data-rn-language>
            @csrf
            <button type="submit" name="preferred_locale" value="bn" class="rn-m-segment-btn{{ $mobileLocale === 'bn' ? ' is-active' : '' }}" @if($mobileLocale === 'bn') aria-pressed="true" @endif lang="bn">বাংলা</button>
            <button type="submit" name="preferred_locale" value="en" class="rn-m-segment-btn{{ $mobileLocale === 'en' ? ' is-active' : '' }}" @if($mobileLocale === 'en') aria-pressed="true" @endif lang="en">English</button>
        </form>

        <a href="{{ route('logout') }}" class="rn-m-signout"><i class="ri-logout-box-r-line" aria-hidden="true"></i><span>{{ __('mobile.sign_out') }}</span></a>
        @if($mobileUser)
            <p class="rn-m-signed-in">{{ $mobileUser->fullName }}</p>
        @endif
    </div>
</section>
@endauth
