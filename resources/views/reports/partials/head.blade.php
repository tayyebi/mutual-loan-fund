<div class="page-head">
    <div>
        <p class="breadcrumb"><a href="{{ route('g.reports.index', $group) }}">{{ __('reports.head.breadcrumb') }}</a></p>
        <h1>{{ $title }}</h1>
        <p class="muted small">
            @if ($asOf)
                {{ __('reports.head.as_of') }} <x-datetime :value="$asOf" />
            @else
                {{ __('reports.head.as_of_today') }}
            @endif
            @if (! empty($from)) · {{ __('reports.head.from') }} <x-datetime :value="$from" /> @endif
            · {{ __('reports.head.figures_in', ['currency' => $currency]) }}
        </p>
    </div>

    <form method="GET" class="filters">
        @if (! empty($showFrom))
            <div class="field">
                <label for="from">{{ __('reports.head.field_from') }}</label>
                <input id="from" name="from" type="date" value="{{ $from?->toDateString() }}">
            </div>
        @endif
        <div class="field">
            <label for="as_of">{{ __('reports.head.field_as_of') }}</label>
            <input id="as_of" name="as_of" type="date" value="{{ $asOf?->toDateString() }}">
        </div>
        <button class="btn btn-small">{{ __('reports.head.apply') }}</button>
    </form>
</div>
