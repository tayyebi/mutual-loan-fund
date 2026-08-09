@extends('layouts.app')
@section('title', 'Funds')

@section('content')
    <div class="page-head">
        <div>
            <h1>Funds</h1>
            <p class="muted small">
                Every fund on the platform. Names, status and member counts only —
                members, transactions, loans and ledgers stay private to each fund.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Fund</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Members</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($funds as $fund)
                    <tr>
                        <td>
                            <a href="{{ route('admin.funds.show', $fund) }}">{{ $fund->name }}</a>
                            <br><span class="small muted mono">{{ $fund->slug }}</span>
                        </td>
                        <td><x-status :value="$fund->status" /></td>
                        <td class="small muted">{{ $fund->creator?->name ?? '—' }}</td>
                        <td class="num">{{ $fund->active_memberships_count }}</td>
                        <td>
                            <div class="actions">
                                @if ($fund->status === \App\Models\Group::STATUS_SUSPENDED)
                                    <form method="POST" action="{{ route('admin.funds.reinstate', $fund) }}">
                                        @csrf
                                        <button class="btn btn-small">Reinstate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.funds.suspend', $fund) }}">
                                        @csrf
                                        <button class="btn btn-small btn-danger">Suspend</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">No funds yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $funds->links('pagination') }}
    </div>
@endsection
