@extends('layouts.app')
@section('title', __('reports.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('reports.index.title') }}</h1>
            <p class="muted small">{{ __('reports.index.subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-3">
        @foreach ($reports as $key => $title)
            <div class="card">
                <h2 style="margin:0 0 0.4rem"><a href="{{ route('g.reports.show', [$group, $key]) }}">{{ $title }}</a></h2>
                <p class="small muted" style="margin:0">
                    {{ match ($key) {
                        'trial-balance' => __('reports.index.descriptions.trial-balance'),
                        'balance-sheet' => __('reports.index.descriptions.balance-sheet'),
                        'income-statement' => __('reports.index.descriptions.income-statement'),
                        'treasuries' => __('reports.index.descriptions.treasuries'),
                        'receivables' => __('reports.index.descriptions.receivables'),
                        'cost-centers' => __('reports.index.descriptions.cost-centers'),
                        'gold' => __('reports.index.descriptions.gold'),
                        default => '',
                    } }}
                </p>
            </div>
        @endforeach
    </div>
@endsection
