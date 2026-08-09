@props([
    'value',
    'currency' => null,
    'decimals' => 2,
    'signed' => false,
])

@php
    // Accepts a Decimal, a Money, or a plain numeric string from the database.
    $decimal = $value instanceof \App\Domain\Money\Money ? $value->amount : $value;
    $decimal = $decimal instanceof \App\Domain\Money\Decimal
        ? $decimal
        : \App\Domain\Money\Decimal::of((string) ($decimal ?: '0'));

    $unit = $currency ?? ($value instanceof \App\Domain\Money\Money ? $value->currency : null);
    $places = $unit ? (int) config("fund.currencies.{$unit}.scale", $decimals) : $decimals;
    $formatted = $decimal->format($places);
    $class = $signed && ! $decimal->isZero() ? ($decimal->isNegative() ? 'neg' : 'pos') : '';
@endphp

<span class="num {{ $class }}">{{ $signed && $decimal->isPositive() ? '+' : '' }}{{ $formatted }}@if ($unit) <span class="muted">{{ $unit }}</span>@endif</span>
