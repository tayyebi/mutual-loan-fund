@extends('layouts.app')
@section('title', 'Reports')

@section('content')
    <div class="page-head">
        <div>
            <h1>Reports</h1>
            <p class="muted small">Every figure is derived from posted journal lines.</p>
        </div>
    </div>

    <div class="grid grid-3">
        @foreach ($reports as $key => $title)
            <div class="card">
                <h2 style="margin:0 0 0.4rem"><a href="{{ route('g.reports.show', [$group, $key]) }}">{{ $title }}</a></h2>
                <p class="small muted" style="margin:0">
                    {{ match ($key) {
                        'trial-balance' => 'Debits and credits per account, and the proof they agree.',
                        'balance-sheet' => 'Assets against liabilities and equity.',
                        'income-statement' => 'Income, expenses and the net result.',
                        'treasuries' => 'What the fund holds, and where.',
                        'receivables' => 'Outstanding principal by member.',
                        'cost-centers' => 'Activity grouped by who it belongs to.',
                        'gold' => 'The fund expressed in grams of 18K gold.',
                        default => '',
                    } }}
                </p>
            </div>
        @endforeach
    </div>
@endsection
