<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt['document_label'] }} · {{ $receipt['document_number'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/rn-tokens.css') }}?v={{ filemtime(public_path('css/rn-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/rn-documents.css') }}?v={{ filemtime(public_path('css/rn-documents.css')) }}">
</head>
<body data-rn-document-preview data-rn-paper="{{ $paper }}">
    <nav class="rn-document-toolbar" aria-label="{{ __('common.actions') }}">
        <button type="button" data-document-back data-back-url="{{ route('dashboard') }}">{{ __('common.back') }}</button>
        <button type="button" data-document-print>{{ __('common.print') }}</button>
        <button type="button" data-document-pdf>{{ __('common.pdf') }}</button>
        <button type="button" data-document-open-share>{{ __('common.share') }}</button>
    </nav>
    <main class="rn-document-preview" aria-label="{{ $receipt['document_label'] }} preview">
    @include('documents.'.$templateType, ['receipt' => $receipt, 'business' => $business, 'paper' => $paper])
    </main>
    @include('documents.actions', ['business' => $business, 'paper' => $paper])
    <script src="{{ asset('vendor/rn-document-tools.js') }}?v={{ filemtime(public_path('vendor/rn-document-tools.js')) }}" defer></script>
    <script src="{{ asset('js/rn-mobile-documents.js') }}?v={{ filemtime(public_path('js/rn-mobile-documents.js')) }}" defer></script>
</body>
</html>
