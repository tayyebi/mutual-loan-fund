@props(['value'])

@php
    $tone = match ($value) {
        'verified', 'active', 'posted', 'published', 'paid', 'fully_repaid', 'approved', 'open' => 'badge-ok',
        'pending', 'requested', 'draft', 'partially_paid', 'disbursed' => 'badge-warn',
        'rejected', 'overdue', 'defaulted', 'suspended', 'reversed' => 'badge-danger',
        default => '',
    };

    $label = match ($value) {
        'verified' => __('components.status.verified'),
        'active' => __('components.status.active'),
        'posted' => __('components.status.posted'),
        'published' => __('components.status.published'),
        'paid' => __('components.status.paid'),
        'fully_repaid' => __('components.status.fully_repaid'),
        'approved' => __('components.status.approved'),
        'open' => __('components.status.open'),
        'pending' => __('components.status.pending'),
        'requested' => __('components.status.requested'),
        'draft' => __('components.status.draft'),
        'partially_paid' => __('components.status.partially_paid'),
        'disbursed' => __('components.status.disbursed'),
        'rejected' => __('components.status.rejected'),
        'overdue' => __('components.status.overdue'),
        'defaulted' => __('components.status.defaulted'),
        'suspended' => __('components.status.suspended'),
        'reversed' => __('components.status.reversed'),
        default => ucfirst(str_replace('_', ' ', (string) $value)),
    };
@endphp

<span class="badge {{ $tone }}">{{ $label }}</span>
