@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="grid grid-side">
        <div class="card">
            <p class="figure-sub">Fund assets</p>
            <p class="figure">{{ $valuation['grams']->format(4) }}</p>
            <p class="small muted">grams of 18K gold ({{ $goldUnit }})</p>

            @if ($valuation['unvalued_lines'] > 0)
                <div class="alert alert-warn">
                    {{ $valuation['unvalued_lines'] }} posted lines carry no gold snapshot: no
                    rate existed for their currency at the time they were posted. They are
                    excluded rather than valued at today's rate.
                </div>
            @endif

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Treasury</th>
                        <th class="num">Native balance</th>
                        <th class="num">≈ 18K gold today</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($valuation['treasuries'] as $row)
                        <tr>
                            <td>{{ $row['treasury']->name }}</td>
                            <td class="num"><x-amount :value="$row['balance']" /></td>
                            <td class="num">
                                @if ($row['gold'])
                                    {{ $row['gold']->format(4) }} g
                                @else
                                    <span class="muted">no rate</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty colspan="3">No treasuries.</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Two different figures</h3>
            <p class="small muted">
                The headline total is the sum of valuations frozen when each line was
                posted — what the fund's assets were worth in gold as they arrived.
            </p>
            <p class="small muted">
                The treasury column re-values today's balances at today's rate. The two
                differ when the gold price has moved, and both are correct answers to
                different questions.
            </p>
        </div>
    </div>
@endsection
