<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt['document_label'] }} · {{ $receipt['document_number'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/rn-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rn-documents.css') }}">
</head>
<body>
    @include('documents.'.$templateType, ['receipt' => $receipt, 'business' => $business, 'paper' => $paper])
    <script src="{{ asset('vendor/rn-document-tools.js') }}" defer></script>
    <script src="{{ asset('js/rn-mobile-documents.js') }}" defer></script>
</body>
</html>
