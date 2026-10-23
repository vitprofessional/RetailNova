<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#14736F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="RetailNova">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
@if (file_exists(public_path('pwa/apple-touch-icon.png')))
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('pwa/apple-touch-icon.png') }}">
@endif
@if (file_exists(public_path('pwa/icon-192.png')))
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('pwa/icon-192.png') }}">
@endif
<link rel="stylesheet" href="{{ asset('css/pwa.css') }}">
<script src="{{ asset('js/pwa.js') }}" defer></script>
