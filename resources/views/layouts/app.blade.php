<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Retail Nova') }}</title>
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
