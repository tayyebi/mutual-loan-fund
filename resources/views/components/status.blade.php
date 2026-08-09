@props(['value'])

@php
    $tone = match ($value) {
        'verified', 'active', 'posted', 'published', 'paid', 'fully_repaid', 'approved', 'open' => 'badge-ok',
        'pending', 'requested', 'draft', 'partially_paid', 'disbursed' => 'badge-warn',
        'rejected', 'overdue', 'defaulted', 'suspended', 'reversed' => 'badge-danger',
        default => '',
    };
@endphp

<span class="badge {{ $tone }}">{{ ucfirst(str_replace('_', ' ', (string) $value)) }}</span>
