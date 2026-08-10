@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="grid grid-side">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <tbody>
                    @foreach ([\App\Models\Account::TYPE_ASSET => __('reports.balance_sheet.assets'), \App\Models\Account::TYPE_LIABILITY => __('reports.balance_sheet.liabilities'), \App\Models\Account::TYPE_EQUITY => __('reports.balance_sheet.equity')] as $type => $label)
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
                        <td>{{ __('reports.balance_sheet.result_for_period') }}</td>
                        <td class="num"><x-amount :value="$sheet['result']" signed /></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>{{ __('reports.balance_sheet.assets') }}</td>
                        <td class="num"><x-amount :value="$sheet['assets']" /></td>
                    </tr>
                    <tr>
                        <td>{{ __('reports.balance_sheet.liabilities_plus_equity') }}</td>
                        <td class="num"><x-amount :value="$sheet['liabilities']->plus($sheet['equity'])" /></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>{{ __('reports.balance_sheet.balance_title') }}</h3>
            @if ($sheet['balanced'])
                <p><span class="badge badge-ok">{{ __('reports.common.balanced') }}</span></p>
            @else
                <p><span class="badge badge-danger">{{ __('reports.common.out_by') }}
                    {{ $sheet['assets']->minus($sheet['liabilities']->plus($sheet['equity']))->format(2) }}</span></p>
            @endif
            <p class="small muted">
                {{ __('reports.balance_sheet.note') }}
            </p>
        </div>
    </div>
@endsection
