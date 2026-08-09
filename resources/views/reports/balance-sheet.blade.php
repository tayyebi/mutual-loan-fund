@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="grid grid-side">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <tbody>
                    @foreach ([\App\Models\Account::TYPE_ASSET => 'Assets', \App\Models\Account::TYPE_LIABILITY => 'Liabilities', \App\Models\Account::TYPE_EQUITY => 'Equity'] as $type => $label)
                        <tr class="row-header">
                            <td colspan="2">{{ $label }}</td>
                        </tr>
                        @foreach ($rows->where('account.type', $type) as $row)
                            <tr>
                                <td style="padding-left:1.5rem">
                                    <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">{{ $row['account']->label() }}</a>
                                </td>
                                <td class="num"><x-amount :value="$row['balance']" signed /></td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="row-header">
                        <td>Result for the period</td>
                        <td class="num"><x-amount :value="$sheet['result']" signed /></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>Assets</td>
                        <td class="num"><x-amount :value="$sheet['assets']" /></td>
                    </tr>
                    <tr>
                        <td>Liabilities + equity (including result)</td>
                        <td class="num"><x-amount :value="$sheet['liabilities']->plus($sheet['equity'])" /></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Balance</h3>
            @if ($sheet['balanced'])
                <p><span class="badge badge-ok">balanced</span></p>
            @else
                <p><span class="badge badge-danger">out by
                    {{ $sheet['assets']->minus($sheet['liabilities']->plus($sheet['equity']))->format(2) }}</span></p>
            @endif
            <p class="small muted">
                Income and expenses for the period are folded into equity as the result;
                without that the sheet would not balance.
            </p>
        </div>
    </div>
@endsection
