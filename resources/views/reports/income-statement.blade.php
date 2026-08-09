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
                        <th>Account</th>
                        <th class="num">Amount ({{ $currency }})</th>
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
                        <x-empty colspan="2">No income or expenses in this period.</x-empty>
                    @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>Income</td>
                        <td class="num"><x-amount :value="$statement['income']" /></td>
                    </tr>
                    <tr>
                        <td>Expenses</td>
                        <td class="num"><x-amount :value="$statement['expenses']" /></td>
                    </tr>
                    <tr>
                        <td><strong>Net result</strong></td>
                        <td class="num"><x-amount :value="$statement['net']" signed /></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Interest</h3>
            <p class="small muted">
                Interest income appears here only when a repayment carrying interest has
                been posted. The policy sets the contractual rate; the ledger records what
                was actually recognised.
            </p>
        </div>
    </div>
@endsection
