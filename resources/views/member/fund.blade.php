@extends('layouts.app')
@section('title', __('member.fund.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('member.fund.heading', ['fund' => $group->name]) }}</h1>
            <p class="muted small">{{ __('member.fund.intro') }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('u.fund.rules', $group) }}">{{ __('member.fund.the_rules') }}</a>
            <a class="btn" href="{{ route('u.fund.members', $group) }}">{{ __('member.fund.the_members') }}</a>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('member.fund.total_value') }}</p>
            <p class="figure">{{ $summary['gold']->format(4) }}</p>
            <p class="small muted">{{ __('member.fund.gold_grams', ['unit' => $goldUnit]) }}</p>
            @if ($summary['unvalued_lines'] > 0)
                <p class="small muted" style="margin:0">
                    {{ __('member.fund.unvalued_lines', ['count' => $summary['unvalued_lines']]) }}
                </p>
            @endif
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.fund.lent_out') }}</p>
            @forelse ($summary['outstanding_loans'] as $currency => $amount)
                <p class="figure" style="font-size:1.4rem"><x-amount :value="$amount" :currency="$currency" /></p>
            @empty
                <p class="figure" style="font-size:1.4rem">{{ __('member.fund.none') }}</p>
            @endforelse
            <p class="small muted" style="margin:0">{{ __('member.fund.lent_out_note') }}</p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.fund.members') }}</p>
            <p class="figure">{{ $memberCount }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('u.fund.members', $group) }}">{{ __('member.fund.see_everyone') }}</a>
            </p>
        </div>
    </div>

    <div class="grid grid-side" style="margin-top:1rem">
        <div class="card">
            <h2>{{ __('member.fund.where_the_money_is') }}</h2>
            <p class="small muted">{{ __('member.fund.treasuries_note') }}</p>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('member.fund.col_holding') }}</th>
                        <th class="num">{{ __('member.fund.col_balance') }}</th>
                        <th class="num">{{ __('member.fund.col_approx_gold') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($summary['treasuries'] as $row)
                        <tr>
                            <td>
                                {{ $row['treasury']->name }}
                                <span class="badge">{{ $row['treasury']->type }}</span>
                            </td>
                            <td class="num"><x-amount :value="$row['balance']" /></td>
                            <td class="num">
                                @if ($row['gold'])
                                    {{ $row['gold']->format(4) }} g
                                @else
                                    <span class="muted">{{ __('member.fund.no_rate') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty colspan="3">{{ __('member.fund.no_treasuries') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('member.fund.the_rules') }}</h2>
            @if ($policy)
                <p class="small">
                    {{ __('member.fund.governed_by', ['version' => $policy->version]) }}
                    <x-datetime :value="$policy->effective_from" />.
                </p>
                <p class="small muted">{{ __('member.fund.rules_note') }}</p>
                <p class="small" style="margin:0">
                    <a href="{{ route('u.fund.rules', $group) }}">{{ __('member.fund.read_the_rules') }}</a>
                </p>
            @else
                <p class="small muted">{{ __('member.fund.no_active_policy') }}</p>
            @endif
        </div>
    </div>
@endsection
