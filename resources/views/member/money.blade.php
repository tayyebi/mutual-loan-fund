@extends('layouts.app')
@section('title', __('member.money.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('member.money.heading') }}</h1>
            <p class="muted small">{{ __('member.money.intro') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('u.money.contribute', $group) }}">{{ __('member.money.contribute') }}</a>
    </div>

    @unless ($tracked)
        <div class="alert alert-info">{{ __('member.money.not_tracked') }}</div>
    @endunless

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('member.money.paid_in') }}</p>
            <p class="figure" style="font-size:1.4rem">
                <x-amount :value="$position['contributed']" :currency="$position['currency']" />
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.money.lent_to_you') }}</p>
            <p class="figure" style="font-size:1.4rem">
                <x-amount :value="$position['outstanding']" :currency="$position['currency']" />
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.money.net_worth_today') }}</p>
            @if ($position['gold'])
                <p class="figure" style="font-size:1.4rem">{{ $position['gold']->format(4) }} <span class="muted">{{ $goldUnit }}</span></p>
                <p class="small muted" style="margin:0">{{ __('member.money.gold_note') }}</p>
            @else
                <p class="figure" style="font-size:1.4rem">{{ __('member.money.no_rate') }}</p>
                <p class="small muted" style="margin:0">{{ __('member.money.no_rate_note') }}</p>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:1rem">
        <div class="card-head">
            <h2>{{ __('member.money.your_payments') }}</h2>
            <a class="small" href="{{ route('u.activity', $group) }}">{{ __('member.money.all_activity') }}</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('member.money.col_date') }}</th>
                    <th class="num">{{ __('member.money.col_amount') }}</th>
                    <th>{{ __('member.money.col_paid_into') }}</th>
                    <th>{{ __('member.money.col_status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($contributions as $contribution)
                    <tr>
                        <td class="num">
                            <a href="{{ route('u.activity.show', [$group, $contribution]) }}">
                                <x-datetime :value="$contribution->occurred_on" />
                            </a>
                        </td>
                        <td class="num"><x-amount :value="$contribution->amount" :currency="$contribution->currency" /></td>
                        <td class="small muted">{{ $contribution->treasury?->name ?? '—' }}</td>
                        <td><x-status :value="$contribution->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="4">{{ __('member.money.no_contributions') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        <p class="small muted" style="margin:0.8rem 0 0">
            {{ __('member.money.calculated_note') }}
        </p>
    </div>
@endsection
