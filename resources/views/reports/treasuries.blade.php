@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Treasury</th>
                    <th>Type</th>
                    <th>Held at</th>
                    <th class="num">Native balance</th>
                    <th class="num">≈ 18K gold</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['treasury']->name }}</td>
                        <td class="small muted">{{ $row['treasury']->type }} · {{ $row['treasury']->network ?? 'bank' }}</td>
                        <td class="mono small truncate">{{ $row['treasury']->external_identifier ?? '—' }}</td>
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
                    <x-empty colspan="5">No treasuries.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            Native currencies stay authoritative. The gold column is a valuation, computed
            at the selected date's rate.
        </p>
    </div>
@endsection
