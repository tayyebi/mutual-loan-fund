@props([
    'value',
    'format' => 'j M Y',
])

@php
    // Display-only: re-renders an already-computed Carbon value in the
    // viewer's timezone and language. Never used for business-date logic —
    // see .ai/PROJECT.md's "Locale / preferences" section.
    $zoned = $value?->copy()->setTimezone(auth()->user()?->timezone ?? config('app.timezone'));
@endphp

@if ($zoned)
    <span title="{{ $zoned->toIso8601String() }}">{{ $zoned->translatedFormat($format) }}</span>
@else
    <span class="muted">—</span>
@endif
