<div class="page-head">
    <div>
        <p class="breadcrumb"><a href="{{ route('g.reports.index', $group) }}">Reports</a></p>
        <h1>{{ $title }}</h1>
        <p class="muted small">
            {{ $asOf ? 'As at '.$asOf->format('j M Y') : 'As at today' }}
            @if (! empty($from)) · from {{ $from->format('j M Y') }} @endif
            · figures in {{ $currency }} unless stated
        </p>
    </div>

    <form method="GET" class="filters">
        @if (! empty($showFrom))
            <div class="field">
                <label for="from">From</label>
                <input id="from" name="from" type="date" value="{{ $from?->toDateString() }}">
            </div>
        @endif
        <div class="field">
            <label for="as_of">As at</label>
            <input id="as_of" name="as_of" type="date" value="{{ $asOf?->toDateString() }}">
        </div>
        <button class="btn btn-small">Apply</button>
    </form>
</div>
