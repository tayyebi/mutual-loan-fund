<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', __('nav.default_title'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="auth-shell">
    <div class="auth-card">
        <h1 class="brand">{{ __('nav.brand') }}</h1>
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
