@extends('layouts.app')
@section('title', $costCenter->label())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.cost-centers.index', $group) }}">Cost centers</a></p>
            <h1>{{ $costCenter->label() }}</h1>
            <p class="muted small">
                @if ($costCenter->member)
                    <a href="{{ route('g.members.show', [$group, $costCenter->member]) }}">{{ $costCenter->member->displayName() }}</a> ·
                @endif
                <x-status :value="$costCenter->status" />
            </p>
        </div>
    </div>

    <div class="card">
        <h2>Statement</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Account</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                    <th class="num">Balance ({{ $currency }})</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($statement as $row)
                    <tr>
                        <td>
                            <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">{{ $row['account']->label() }}</a>
                        </td>
                        <td class="num"><x-amount :value="$row['debit']" /></td>
                        <td class="num"><x-amount :value="$row['credit']" /></td>
                        <td class="num"><x-amount :value="$row['balance']" signed /></td>
                    </tr>
                @empty
                    <x-empty colspan="4">Nothing has been attributed to this cost center.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
