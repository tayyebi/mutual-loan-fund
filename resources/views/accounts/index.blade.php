@extends('layouts.app')
@section('title', __('accounts.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('accounts.index.heading') }}</h1>
            <p class="muted small">
                {{ __('accounts.index.intro') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('accounts.index.col_code') }}</th>
                    <th>{{ __('accounts.index.col_account') }}</th>
                    <th>{{ __('accounts.index.col_type') }}</th>
                    <th>{{ __('accounts.index.col_cost_center') }}</th>
                    <th class="num">{{ __('accounts.index.col_balance', ['currency' => $currency]) }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($accounts as $account)
                    @php($row = $balances->get($account->getKey()))
                    <tr class="{{ $account->isHeader() ? 'row-header' : '' }}">
                        <td class="mono">{{ $account->code }}</td>
                        <td style="{{ $account->isHeader() ? '' : 'padding-left:1.5rem' }}">
                            <a href="{{ route('g.accounts.show', [$group, $account]) }}">{{ $account->name }}</a>
                            @unless ($account->is_active)<span class="badge">{{ __('accounts.index.inactive_badge') }}</span>@endunless
                        </td>
                        <td class="small muted">{{ $account->type }}</td>
                        <td class="small muted">{{ $account->requires_cost_center ? __('accounts.index.required_label') : '—' }}</td>
                        <td class="num">
                            @if ($row)
                                <x-amount :value="$row['balance']" signed />
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            {{ __('accounts.index.footer_note') }}
        </p>
    </div>
@endsection
