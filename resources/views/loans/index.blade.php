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
    <div class="list-rows">
        @forelse ($loans as $loan)
            <x-list-row href="@surface('loan.show', $group, $loan)">
                @if ($isAdmin)
                    <x-slot:avatar>
                        <x-avatar :name="$loan->member?->displayName() ?? '·'" size="sm" />
                    </x-slot:avatar>
                @endif

                {{ $loan->reference }}
                @if ($isAdmin) <span class="small muted">· {{ $loan->member?->displayName() }}</span> @endif

                <x-slot:meta>
                    <x-amount :value="$loan->principal" :currency="$loan->currency" />
                    · {{ rtrim(rtrim($loan->interest_rate, '0'), '.') ?: '0' }}%
                    · {{ $loan->term_months }} {{ __('loans.index.term_unit') }}
                    · v{{ $loan->policyVersion->version }}
                </x-slot:meta>

                <x-slot:trailing>
                    <x-status :value="$loan->status" />
                </x-slot:trailing>
            </x-list-row>
        @empty
            <x-empty as="list">{{ __('loans.index.empty') }}</x-empty>
        @endforelse
    </div>
    </div>
@endsection
