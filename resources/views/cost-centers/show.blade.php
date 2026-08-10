@extends('layouts.app')
@section('title', $costCenter->label())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.cost-centers.index', $group) }}">{{ __('cost_centers.show.breadcrumb') }}</a></p>
            <h1>{{ $costCenter->label() }}</h1>
            <p class="muted small">
                @if ($costCenter->member)
                    <a href="{{ route('g.members.show', [$group, $costCenter->member]) }}">{{ $costCenter->member->displayName() }}</a> ·
                @endif
                <x-status :value="$costCenter->status" />
            </p>
        </div>
    </div>

    <div class="card">
        <h2>{{ __('cost_centers.show.statement_title') }}</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('cost_centers.show.table_account') }}</th>
                    <th class="num">{{ __('cost_centers.show.table_debit') }}</th>
                    <th class="num">{{ __('cost_centers.show.table_credit') }}</th>
                    <th class="num">{{ __('cost_centers.show.table_balance', ['currency' => $currency]) }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($statement as $row)
                    <tr>
                        <td>
                            <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">{{ $row['account']->label() }}</a>
                        </td>
                        <td class="num"><x-amount :value="$row['debit']" /></td>
                        <td class="num"><x-amount :value="$row['credit']" /></td>
                        <td class="num"><x-amount :value="$row['balance']" signed /></td>
                    </tr>
                @empty
                    <x-empty colspan="4">{{ __('cost_centers.show.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
