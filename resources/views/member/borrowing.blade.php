@extends('layouts.app')
@section('title', __('member.borrowing.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('member.borrowing.heading') }}</h1>
            <p class="muted small">{{ __('member.borrowing.intro') }}</p>
        </div>
        @if ($policy)
            <a class="btn btn-primary" href="{{ route('u.borrowing.request', $group) }}">{{ __('member.borrowing.request') }}</a>
        @endif
    </div>

    @unless ($policy)
        <div class="alert alert-warn">{{ __('member.borrowing.no_active_policy') }}</div>
    @else
        <div class="card">
            <h2>{{ __('member.borrowing.where_you_stand') }}</h2>

            <dl class="deflist">
                <dt>{{ __('member.borrowing.can_borrow_now') }}</dt>
                <dd>
                    @if ($eligibility?->available())
                        <x-amount :value="$eligibility->available()" :currency="$eligibility->currency" />
                    @else
                        <span class="muted">{{ __('member.borrowing.no_limit_set') }}</span>
                    @endif
                </dd>

                <dt>{{ __('member.borrowing.already_owed') }}</dt>
                <dd><x-amount :value="$eligibility->outstanding" :currency="$eligibility->currency" /></dd>

                <dt>{{ __('member.borrowing.open_loans') }}</dt>
                <dd>
                    {{ __('member.borrowing.of_maximum', [
                        'count' => $eligibility->activeLoans,
                        'max' => $eligibility->maximumActiveLoans,
                    ]) }}
                </dd>

                @if ($eligibility->requiredMembershipDays > 0)
                    <dt>{{ __('member.borrowing.membership_length') }}</dt>
                    <dd>
                        {{ __('member.borrowing.days_of_required', [
                            'days' => $eligibility->membershipDays,
                            'required' => $eligibility->requiredMembershipDays,
                        ]) }}
                    </dd>
                @endif
            </dl>

            @if ($eligibility && $eligibility->failures !== [])
                {{--
                    The same LoanEligibility that will decide the real request,
                    so what is shown here and what the server allows cannot
                    disagree.
                --}}
                <div class="alert alert-warn" style="margin-bottom:0">
                    {{ __('member.borrowing.cannot_borrow_now') }}
                    <ul>
                        @foreach ($eligibility->failures as $failure)
                            <li>{{ $failure }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endunless

    <div class="card" style="margin-top:1rem">
        <h2>{{ __('member.borrowing.what_you_owe') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('member.borrowing.col_reference') }}</th>
                    <th class="num">{{ __('member.borrowing.col_borrowed') }}</th>
                    <th class="num">{{ __('member.borrowing.col_still_owed') }}</th>
                    <th>{{ __('member.borrowing.col_next_payment') }}</th>
                    <th>{{ __('member.borrowing.col_status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($outstanding as $loan)
                    @php($next = $loan->nextInstallment())
                    <tr>
                        <td><a href="{{ route('u.borrowing.loan', [$group, $loan]) }}">{{ $loan->reference }}</a></td>
                        <td class="num"><x-amount :value="$loan->principal" :currency="$loan->currency" /></td>
                        <td class="num"><x-amount :value="$loan->outstandingPrincipal()" :currency="$loan->currency" /></td>
                        <td class="num">
                            @if ($next)
                                <x-amount :value="$next->remaining()" :currency="$loan->currency" />
                                <span class="small muted"><x-datetime :value="$next->due_date" /></span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td><x-status :value="$loan->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="5">{{ __('member.borrowing.nothing_owed') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($outstanding->isNotEmpty())
            <p class="small muted" style="margin:0.8rem 0 0">{{ __('member.borrowing.repay_note') }}</p>
        @endif
    </div>

    @if ($settled->isNotEmpty())
        <div class="card" style="margin-top:1rem">
            <h2>{{ __('member.borrowing.past_loans') }}</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('member.borrowing.col_reference') }}</th>
                        <th class="num">{{ __('member.borrowing.col_borrowed') }}</th>
                        <th>{{ __('member.borrowing.col_requested_on') }}</th>
                        <th>{{ __('member.borrowing.col_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($settled as $loan)
                        <tr>
                            <td><a href="{{ route('u.borrowing.loan', [$group, $loan]) }}">{{ $loan->reference }}</a></td>
                            <td class="num"><x-amount :value="$loan->principal" :currency="$loan->currency" /></td>
                            <td class="num"><x-datetime :value="$loan->created_at" /></td>
                            <td><x-status :value="$loan->status" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
