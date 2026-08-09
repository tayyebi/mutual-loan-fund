@extends('layouts.app')
@section('title', 'Accounting periods')

@section('content')
    <div class="page-head">
        <div>
            <h1>Accounting periods</h1>
            <p class="muted small">
                A closed period accepts no postings. Corrections for a closed month are
                posted as adjustments in an open one.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Closed</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td class="mono">{{ $period->label() }}</td>
                        <td><x-status :value="$period->status" /></td>
                        <td class="small muted">
                            @if ($period->closed_at)
                                {{ $period->closer?->name }} · {{ $period->closed_at->format('j M Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($period->isClosed())
                                <form method="POST" action="{{ route('g.periods.reopen', [$group, $period]) }}">
                                    @csrf
                                    <button class="btn btn-small">Reopen</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('g.periods.close', [$group, $period]) }}">
                                    @csrf
                                    <button class="btn btn-small">Close</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="4">Periods are created as entries are posted.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            Reopening is possible but audited: it corrects an administrative mistake, not
            a routine step.
        </p>
    </div>
@endsection
