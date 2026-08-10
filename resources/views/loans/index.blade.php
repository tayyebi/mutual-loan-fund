@extends('layouts.app')
@section('title', __('loans.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('loans.index.heading') }}</h1>
            <p class="muted small">
                {{ __('loans.index.intro') }}
            </p>
        </div>
        {{-- Requesting a loan is a member's act and lives on /u; this list is
             the administrator's queue of everyone else's. --}}
        <a class="btn" href="{{ route('u.borrowing', $group) }}">{{ __('loans.index.my_borrowing_link') }}</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('loans.index.col_reference') }}</th>
                    @if ($isAdmin)<th>{{ __('loans.index.col_member') }}</th>@endif
                    <th class="num">{{ __('loans.index.col_principal') }}</th>
                    <th class="num">{{ __('loans.index.col_rate') }}</th>
                    <th class="num">{{ __('loans.index.col_term') }}</th>
                    <th>{{ __('loans.index.col_policy') }}</th>
                    <th>{{ __('loans.index.col_status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td><a href="@surface('loan.show', $group, $loan)">{{ $loan->reference }}</a></td>
                        @if ($isAdmin)<td>{{ $loan->member?->displayName() }}</td>@endif
                        <td class="num"><x-amount :value="$loan->principal" :currency="$loan->currency" /></td>
                        <td class="num">{{ rtrim(rtrim($loan->interest_rate, '0'), '.') ?: '0' }}%</td>
                        <td class="num">{{ $loan->term_months }} {{ __('loans.index.term_unit') }}</td>
                        <td class="small">
                            <a href="@surface('policy.show', $group, $loan->policyVersion->version)">
                                v{{ $loan->policyVersion->version }}
                            </a>
                        </td>
                        <td><x-status :value="$loan->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="{{ $isAdmin ? 7 : 6 }}">{{ __('loans.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
