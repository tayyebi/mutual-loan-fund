@extends('layouts.app')
@section('title', $title)

@section('content')
    @php($showFrom = true)
    @include('reports.partials.head')

    <div class="grid grid-side">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('reports.income_statement.table_account') }}</th>
                        <th class="num">{{ __('reports.income_statement.table_amount', ['currency' => $currency]) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($statement['rows'] as $row)
                        <tr>
                            <td>
                                <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">{{ $row['account']->label() }}</a>
                                <span class="small muted">{{ $row['account']->type }}</span>
                            </td>
                            <td class="num"><x-amount :value="$row['balance']" signed /></td>
                        </tr>
                    @empty
                        <x-empty colspan="2">{{ __('reports.income_statement.empty') }}</x-empty>
                    @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>{{ __('reports.income_statement.income') }}</td>
                        <td class="num"><x-amount :value="$statement['income']" /></td>
                    </tr>
                    <tr>
                        <td>{{ __('reports.income_statement.expenses') }}</td>
                        <td class="num"><x-amount :value="$statement['expenses']" /></td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('reports.income_statement.net_result') }}</strong></td>
                        <td class="num"><x-amount :value="$statement['net']" signed /></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>{{ __('reports.income_statement.interest_title') }}</h3>
            <p class="small muted">
                {{ __('reports.income_statement.interest_note') }}
            </p>
        </div>
    </div>
@endsection
