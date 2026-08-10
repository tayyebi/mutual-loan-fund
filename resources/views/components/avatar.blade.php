@props(['name' => '', 'size' => null])

@php
    $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
    $tone = (crc32((string) $name) % 5) + 1;
    $sizeClass = match ($size) {
        'sm' => 'avatar-sm',
        'lg' => 'avatar-lg',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => trim("avatar avatar-{$tone} {$sizeClass}")]) }}>{{ $initials }}</span>
