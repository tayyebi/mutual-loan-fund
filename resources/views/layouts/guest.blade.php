<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('nav.default_title'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main>
    <div class="container" style="max-width: 26rem; padding-top: 3rem;">
        <h1 class="brand" style="margin-bottom: 1.5rem;">{{ __('nav.brand') }}</h1>
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
