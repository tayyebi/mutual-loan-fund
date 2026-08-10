@props(['name'])

@php
    /*
     * A small, self-authored inline-SVG icon set — no external icon font or
     * CDN, in keeping with the app's no-external-resources rule. Every key
     * here corresponds to a NavItem/SurfaceSwitch `icon` value declared in
     * app/Domain/Access/Surfaces/*.php, plus a handful of chrome icons used
     * directly in the layouts.
     */
    $paths = [
        'home' => '<path d="M4 11.5 12 4l8 7.5" /><path d="M6 10v9h12v-9" />',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2.5" /><path d="M3 10h18" /><circle cx="16.5" cy="14" r="1.1" fill="currentColor" stroke="none" />',
        'wallets' => '<rect x="5" y="8" width="16" height="11" rx="2.5" /><path d="M3 5.5h13a2 2 0 0 1 2 2V8" />',
        'borrowing' => '<path d="M4 8h11" /><path d="M11 4l4 4-4 4" /><path d="M20 16H9" /><path d="M13 20l-4-4 4-4" />',
        'activity' => '<path d="M3 12h4l2.5-7 5 14L17 12h4" />',
        'fund' => '<path d="M4 10.5 12 5l8 5.5" /><path d="M5 10.5V19M9.5 10.5V19M14.5 10.5V19M19 10.5V19" /><path d="M4 19h16" />',
        'dashboard' => '<rect x="3.5" y="3.5" width="7.5" height="7.5" rx="1.5" /><rect x="13" y="3.5" width="7.5" height="4.5" rx="1.5" /><rect x="13" y="10.5" width="7.5" height="10" rx="1.5" /><rect x="3.5" y="13.5" width="7.5" height="7" rx="1.5" />',
        'loans' => '<rect x="2.5" y="6" width="19" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /><path d="M6 6v12M18 6v12" />',
        'members' => '<circle cx="8.5" cy="9" r="3" /><path d="M2.5 19c0-3 2.7-5 6-5s6 2 6 5" /><circle cx="17" cy="9.5" r="2.4" /><path d="M15.3 14.3c2.6.3 4.7 2.1 4.7 4.7" />',
        'treasuries' => '<rect x="3.5" y="4" width="17" height="16" rx="2" /><circle cx="12" cy="12" r="3.2" /><path d="M12 10v4M10 12h4" /><path d="M6.5 7.5h.01M17.5 7.5h.01" />',
        'ledger' => '<path d="M5 3.5h11l3 3V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" /><path d="M16 3.5V7h3.3" /><path d="M7.5 12h9M7.5 15.5h9M7.5 8.5h4" />',
        'accounts' => '<path d="M3 6.5a1.5 1.5 0 0 1 1.5-1.5H9l2 2h8.5A1.5 1.5 0 0 1 21 8.5V18a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18Z" />',
        'cost-centers' => '<path d="M12.5 3.5H19a1.5 1.5 0 0 1 1.5 1.5v6.5a1.5 1.5 0 0 1-.44 1.06l-8 8a1.5 1.5 0 0 1-2.12 0l-6.5-6.5a1.5 1.5 0 0 1 0-2.12l8-8a1.5 1.5 0 0 1 1.06-.44Z" /><circle cx="16" cy="8" r="1.3" fill="currentColor" stroke="none" />',
        'periods' => '<rect x="3.5" y="4.5" width="17" height="16" rx="2" /><path d="M3.5 9.5h17" /><path d="M8 3v3M16 3v3" /><path d="M7.5 13.5h3M13.5 13.5h3M7.5 17h3M13.5 17h3" />',
        'reports' => '<path d="M4 20V10M10 20V4M16 20v-7M20 20H4" />',
        'policies' => '<path d="M12 3.5 19 6v6c0 4.5-3 7.5-7 8.5-4-1-7-4-7-8.5V6Z" /><path d="M9 12l2 2 4-4" />',
        'framework' => '<path d="M12 3.5 4 8l8 4.5L20 8Z" /><path d="M4 13l8 4.5L20 13" /><path d="M4 17.5 12 22l8-4.5" />',
        'audit' => '<circle cx="10.5" cy="10.5" r="6.5" /><path d="M15.3 15.3 20 20" />',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
        'exchange' => '<path d="M6 8h13" /><path d="M16 4l3 4-3 4" /><path d="M18 16H5" /><path d="M8 12l-3 4 3 4" />',
        'exit' => '<path d="M14 4.5H7A1.5 1.5 0 0 0 5.5 6v12A1.5 1.5 0 0 0 7 19.5h7" /><path d="M11 12h9.5" /><path d="M17.5 8.5 21 12l-3.5 3.5" />',
        'dot' => '<circle cx="12" cy="12" r="3" fill="currentColor" stroke="none" />',
        'chevron' => '<path d="M9 5l7 7-7 7" />',
        'user' => '<circle cx="12" cy="8" r="4" /><path d="M4.5 20c0-4 3.4-6.5 7.5-6.5s7.5 2.5 7.5 6.5" />',
        'briefcase' => '<rect x="3" y="7.5" width="18" height="12" rx="2" /><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5" /><path d="M3 12.5h18" />',
        'shield' => '<path d="M12 3.5 19 6v6c0 4.5-3 7.5-7 8.5-4-1-7-4-7-8.5V6Z" />',
    ][$name] ?? '<circle cx="12" cy="12" r="3" fill="currentColor" stroke="none" />';
@endphp

<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>{!! $paths !!}</svg>
