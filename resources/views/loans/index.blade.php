@extends('layouts.app')
@section('title', 'Loans')

@section('content')
    <div class="page-head">
        <div>
            <h1>Loans</h1>
            <p class="muted small">
                Each loan is governed for its whole life by the policy version it was
                created under.
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('g.loans.create', $group) }}">Request a loan</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Reference</th>
                    @if ($isAdmin)<th>Member</th>@endif
                    <th class="num">Principal</th>
                    <th class="num">Rate</th>
                    <th class="num">Term</th>
                    <th>Policy</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td><a href="{{ route('g.loans.show', [$group, $loan]) }}">{{ $loan->reference }}</a></td>
                        @if ($isAdmin)<td>{{ $loan->member?->displayName() }}</td>@endif
                        <td class="num"><x-amount :value="$loan->principal" :currency="$loan->currency" /></td>
                        <td class="num">{{ rtrim(rtrim($loan->interest_rate, '0'), '.') ?: '0' }}%</td>
                        <td class="num">{{ $loan->term_months }} mo</td>
                        <td class="small">
                            <a href="{{ route('g.policies.show', [$group, $loan->policyVersion->version]) }}">
                                v{{ $loan->policyVersion->version }}
                            </a>
                        </td>
                        <td><x-status :value="$loan->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="{{ $isAdmin ? 7 : 6 }}">No loans yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
