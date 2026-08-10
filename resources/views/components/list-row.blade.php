@props(['href'])

<a href="{{ $href }}" class="list-row">
    @isset($avatar)
        <span class="list-row-lead">{{ $avatar }}</span>
    @endisset
    <span class="list-row-body">
        <span class="list-row-title">{{ $slot }}</span>
        @isset($meta)
            <span class="list-row-meta">{{ $meta }}</span>
        @endisset
    </span>
    <span class="list-row-trail">
        @isset($trailing)
            {{ $trailing }}
        @endisset
        <x-icon name="chevron" />
    </span>
</a>
