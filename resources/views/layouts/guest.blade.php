<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mutual Loan Fund')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main>
    <div class="container" style="max-width: 26rem; padding-top: 3rem;">
        <h1 class="brand" style="margin-bottom: 1.5rem;">Mutual Loan Fund</h1>
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
