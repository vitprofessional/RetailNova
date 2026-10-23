<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff8fb">
    <title>{{ __('authentication.login_title') }} | RetailNova</title>
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    @include('partials.pwa-head')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
    <meta name="theme-color" content="#fff8fb">
</head>
<body class="rn-login-page">
    <header class="rn-login-topbar">
        <a class="rn-login-mobile-brand" href="{{ route('userLogin') }}" aria-label="RetailNova">
            <img src="{{ asset('logo.png') }}" alt="RetailNova">
        </a>
        @guest('admin')
            <details class="rn-language-picker" data-language-picker>
                <summary class="rn-language-trigger" aria-label="{{ __('authentication.language') }}" aria-controls="rn-language-options" aria-expanded="false">
                    <span class="rn-language-globe" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18M5.6 6.5h12.8M5.6 17.5h12.8"/></svg>
                    </span>
                    <span class="rn-language-current" lang="{{ app()->getLocale() }}">{{ app()->getLocale() === 'bn' ? __('authentication.bangla') : __('authentication.english') }}</span>
                    <svg class="rn-language-chevron" viewBox="0 0 16 16" aria-hidden="true"><path d="m3.5 6 4.5 4 4.5-4"/></svg>
                </summary>
                <div class="rn-language-menu" id="rn-language-options" aria-label="{{ __('authentication.language') }}">
                    <form action="{{ route('locale.guest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="locale" value="en">
                        <button type="submit" lang="en" @if(app()->getLocale() === 'en') aria-current="true" class="is-selected" @endif>{{ __('authentication.english') }}</button>
                    </form>
                    <form action="{{ route('locale.guest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="locale" value="bn">
                        <button type="submit" lang="bn" @if(app()->getLocale() === 'bn') aria-current="true" class="is-selected" @endif>{{ __('authentication.bangla') }}</button>
                    </form>
                </div>
            </details>
        @endguest
    </header>

    <main class="rn-login-layout">
        <section class="rn-login-hero" aria-labelledby="rn-login-brand-heading">
            <div class="rn-pos-scene" aria-hidden="true">
                <img class="rn-pos-art" src="{{ asset('images/login/retailnova-desktop-scene.svg') }}" alt="" loading="eager" decoding="async">
            </div>
            <div class="rn-hero-content">
                <img class="rn-hero-logo" src="{{ asset('logo.png') }}" alt="RetailNova">
                <h1 id="rn-login-brand-heading">{{ __('authentication.hero_title') }}</h1>
                <p class="rn-hero-description">{{ __('authentication.hero_description') }}</p>

                <ul class="rn-capabilities" aria-label="{{ __('authentication.capabilities') }}">
                    <li>
                        <span class="rn-capability-icon rn-capability-pink" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.1 11.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 8H6M10 21h.01M18 21h.01"/></svg></span>
                        <span>{{ __('authentication.feature_sales') }}</span>
                    </li>
                    <li>
                        <span class="rn-capability-icon rn-capability-teal" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8M12 13v9"/></svg></span>
                        <span>{{ __('authentication.feature_inventory') }}</span>
                    </li>
                    <li>
                        <span class="rn-capability-icon rn-capability-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20v-1a6 6 0 0 1 12 0v1H3Zm13-5a4.5 4.5 0 0 1 5 4.5V20h-4"/></svg></span>
                        <span>{{ __('authentication.feature_customers') }}</span>
                    </li>
                    <li>
                        <span class="rn-capability-icon rn-capability-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V11h4v9H4Zm6 0V5h4v15h-4Zm6 0v-7h4v7h-4Z"/></svg></span>
                        <span>{{ __('authentication.feature_reports') }}</span>
                    </li>
                </ul>

            </div>
        </section>

        <section class="rn-login-panel" aria-labelledby="rn-login-title">
            <article class="rn-login-card">
                <div class="rn-card-brand">
                    <img src="{{ asset('logo.png') }}" alt="RetailNova">
                </div>
                <div class="rn-card-heading">
                    <span class="rn-welcome-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4m0-12.8L17 7M7 17l-1.4 1.4"/><circle cx="12" cy="12" r="5"/></svg></span>
                    <h2 id="rn-login-title">{{ __('authentication.welcome_back') }}</h2>
                    <p>{{ __('authentication.login_subtitle') }}</p>
                </div>

                @php
                    $loginNotice = session('login_notice');
                    $loginNoticeText = is_array($loginNotice) ? match (($loginNotice['key'] ?? null)) {
                        'logout_successful' => __('authentication.logout_successful'),
                        'invalid_credentials' => __('common.notification_invalid_credentials'),
                        default => null,
                    } : null;
                    $successMessage = is_array($loginNotice) && ($loginNotice['type'] ?? null) === 'success'
                        ? $loginNoticeText
                        : session('success');
                    $errorMessage = is_array($loginNotice) && ($loginNotice['type'] ?? null) === 'error'
                        ? $loginNoticeText
                        : session('error');
                @endphp
                @if ($successMessage)
                    <div class="rn-alert rn-alert-success" role="status">{{ $successMessage }}</div>
                @endif
                @if ($errorMessage)
                    <div class="rn-alert rn-alert-error" role="alert">{{ $errorMessage }}</div>
                @endif
                @if ($errors->any())
                    <div class="rn-alert rn-alert-error" role="alert">
                        <p>{{ __('authentication.form_error_heading') }}</p>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('adminLogin') }}" class="rn-login-form" method="POST">
                    @csrf
                    <div class="rn-field-group">
                        <label for="userMail">{{ __('authentication.email_or_username') }}</label>
                        <div class="rn-input-wrap @error('userMail') rn-input-invalid @enderror">
                            <svg class="rn-field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                            <input type="email" id="userMail" name="userMail" value="{{ old('userMail') }}" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="{{ __('authentication.email_placeholder') }}" aria-describedby="email-help" required>
                        </div>
                        <span id="email-help" class="rn-sr-only">{{ __('authentication.email_help') }}</span>
                        @error('userMail')<span class="rn-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="rn-field-group">
                        <label for="password">{{ __('authentication.password') }}</label>
                        <div class="rn-input-wrap @error('password') rn-input-invalid @enderror">
                            <svg class="rn-field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3"/></svg>
                            <input type="password" id="password" name="password" autocomplete="current-password" placeholder="{{ __('authentication.password_placeholder') }}" required>
                            <button class="rn-password-toggle" type="button" aria-label="{{ __('authentication.show_password') }}" aria-pressed="false" data-show-label="{{ __('authentication.show_password') }}" data-hide-label="{{ __('authentication.hide_password') }}">
                                <svg class="rn-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                        @error('password')<span class="rn-field-error">{{ $message }}</span>@enderror
                    </div>

                    <button class="rn-sign-in-button" type="submit">
                        <span>{{ __('authentication.sign_in') }}</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-7 7 7-7 7"/></svg>
                    </button>
                </form>

                <div class="rn-card-footer">
                    <span class="rn-footer-dot" aria-hidden="true"></span>
                    <span>{{ __('authentication.secure_login_note') }}</span>
                </div>
                @if ($firstRunOpen)
                    <p class="rn-first-setup">{{ __('authentication.first_setup_prompt') }} <a href="{{ route('superadmin.setup.show') }}">{{ __('authentication.first_setup_link') }}</a></p>
                @endif
            </article>
            <ul class="rn-login-benefits" aria-label="{{ __('authentication.login_benefits') }}">
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                    <span>{{ __('authentication.benefit_secure') }}</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 17h4"/></svg>
                    <span>{{ __('authentication.benefit_sales') }}</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8M12 13v9"/></svg>
                    <span>{{ __('authentication.benefit_inventory') }}</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M8 15v-4h3v4m2 0V7h3v8m2 0V9h2v6"/></svg>
                    <span>{{ __('authentication.benefit_reports') }}</span>
                </li>
            </ul>
            <footer class="rn-login-copyright">{{ __('authentication.product_footer') }}</footer>
        </section>
    </main>
    <script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}" defer></script>
</body>
</html>
