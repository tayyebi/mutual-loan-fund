@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                    <th class="num">Balance</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="mono">{{ $row['account']->code }}</td>
                        <td>
                            <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">{{ $row['account']->name }}</a>
                        </td>
                        <td class="num"><x-amount :value="$row['debit']" /></td>
                        <td class="num"><x-amount :value="$row['credit']" /></td>
                        <td class="num"><x-amount :value="$row['balance']" signed /></td>
                    </tr>
                @empty
                    <x-empty colspan="5">Nothing has been posted yet.</x-empty>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2">Totals ({{ $currency }})</td>
                    <td class="num"><x-amount :value="$totals['debit']" /></td>
                    <td class="num"><x-amount :value="$totals['credit']" /></td>
                    <td class="num">
                        @if ($totals['balanced'])
                            <span class="badge badge-ok">balanced</span>
                        @else
                            <span class="badge badge-danger">out by {{ $totals['debit']->minus($totals['credit'])->format(2) }}</span>
                        @endif
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
