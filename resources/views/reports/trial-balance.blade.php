@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('reports.trial_balance.table_code') }}</th>
                    <th>{{ __('reports.trial_balance.table_account') }}</th>
                    <th class="num">{{ __('reports.trial_balance.table_debit') }}</th>
                    <th class="num">{{ __('reports.trial_balance.table_credit') }}</th>
                    <th class="num">{{ __('reports.trial_balance.table_balance') }}</th>
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
                    <x-empty colspan="5">{{ __('reports.trial_balance.empty') }}</x-empty>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2">{{ __('reports.trial_balance.totals', ['currency' => $currency]) }}</td>
                    <td class="num"><x-amount :value="$totals['debit']" /></td>
                    <td class="num"><x-amount :value="$totals['credit']" /></td>
                    <td class="num">
                        @if ($totals['balanced'])
                            <span class="badge badge-ok">{{ __('reports.common.balanced') }}</span>
                        @else
                            <span class="badge badge-danger">{{ __('reports.common.out_by') }} {{ $totals['debit']->minus($totals['credit'])->format(2) }}</span>
                        @endif
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
