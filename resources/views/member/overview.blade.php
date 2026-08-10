@extends('layouts.app')
@section('title', $group->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('member.overview.heading', ['fund' => $group->name]) }}</h1>
            <p class="muted small">
                @if ($policy)
                    {{ __('member.overview.governed_by', ['version' => $policy->version]) }}
                    <a href="{{ route('u.fund.rules', $group) }}">{{ __('member.overview.read_the_rules') }}</a>
                @else
                    {{ __('member.overview.no_active_policy') }}
                @endif
            </p>
        </div>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('u.money.contribute', $group) }}">{{ __('member.overview.contribute') }}</a>
            <a class="btn" href="{{ route('u.borrowing.request', $group) }}">{{ __('member.overview.request_loan') }}</a>
        </div>
    </div>

    @unless ($overview['tracked'])
        {{--
            A membership with no cost centre has no ledger identity yet, so every
            figure below would read 0.00 — which looks identical to "you have
            contributed nothing". Say which it is rather than showing a confident
            zero.
        --}}
        <div class="alert alert-info">
            {{ __('member.overview.not_tracked') }}
        </div>
    @endunless

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('member.overview.your_stake') }}</p>
            <p class="figure" style="font-size:1.4rem">
                <x-amount :value="$overview['position']['contributed']" :currency="$overview['position']['currency']" />
            </p>
            @if ($overview['position']['gold'])
                <p class="small muted" style="margin:0">
                    {{ __('member.overview.worth_gold', [
                        'grams' => $overview['position']['gold']->format(4),
                        'unit' => $goldUnit,
                    ]) }}
                </p>
            @endif
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('u.money', $group) }}">{{ __('member.overview.see_my_money') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.overview.you_owe') }}</p>
            <p class="figure" style="font-size:1.4rem">
                <x-amount :value="$overview['position']['outstanding']" :currency="$overview['position']['currency']" />
            </p>
            <p class="small muted" style="margin:0">
                {{ trans_choice('member.overview.open_loans', $overview['outstanding']->count(), [
                    'count' => $overview['outstanding']->count(),
                ]) }}
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('u.borrowing', $group) }}">{{ __('member.overview.see_borrowing') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('member.overview.you_could_borrow') }}</p>
            @if ($headroom?->available())
                <p class="figure" style="font-size:1.4rem">
                    <x-amount :value="$headroom->available()" :currency="$headroom->currency" />
                </p>
            @else
                <p class="figure" style="font-size:1.4rem">{{ __('member.overview.no_limit_set') }}</p>
            @endif
            @if ($headroom && $headroom->firstFailure())
                <p class="small muted" style="margin:0">{{ $headroom->firstFailure() }}</p>
            @endif
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('u.borrowing.request', $group) }}">{{ __('member.overview.request_loan') }}</a>
            </p>
        </div>
    </div>

    <div class="grid grid-side" style="margin-top: 1rem;">
        <div class="card">
            <div class="card-head">
                <h2>{{ __('member.overview.your_recent_activity') }}</h2>
                <a class="small" href="{{ route('u.activity', $group) }}">{{ __('member.overview.all_activity') }}</a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('member.overview.col_date') }}</th>
                        <th>{{ __('member.overview.col_what') }}</th>
                        <th class="num">{{ __('member.overview.col_amount') }}</th>
                        <th>{{ __('member.overview.col_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $transaction)
                        <tr>
                            <td class="num"><x-datetime :value="$transaction->occurred_on" format="j M" /></td>
                            <td>
                                <a href="{{ route('u.activity.show', [$group, $transaction]) }}">
                                    {{ $transaction->typeLabel() }}
                                </a>
                            </td>
                            <td class="num"><x-amount :value="$transaction->amount" :currency="$transaction->currency" /></td>
                            <td><x-status :value="$transaction->status" /></td>
                        </tr>
                    @empty
                        <x-empty colspan="4">{{ __('member.overview.nothing_yet') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('member.overview.waiting_on') }}</h2>

            @php($nothingWaiting = $overview['pending'] === 0 && $overview['undecided']->isEmpty())

            @if ($nothingWaiting)
                <p class="small muted">{{ __('member.overview.nothing_waiting') }}</p>
            @else
                <ul class="stack small" style="margin:0; padding-left:1.1rem">
                    @if ($overview['pending'] > 0)
                        <li>
                            {{ trans_choice('member.overview.pending_submissions', $overview['pending'], [
                                'count' => $overview['pending'],
                            ]) }}
                        </li>
                    @endif
                    @foreach ($overview['undecided'] as $loan)
                        <li>
                            <a href="{{ route('u.borrowing.loan', [$group, $loan]) }}">{{ $loan->reference }}</a>
                            — <x-status :value="$loan->status" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="small muted" style="margin-top:0.8rem">
                {{ __('member.overview.verification_note') }}
            </p>
        </div>
    </div>
@endsection
