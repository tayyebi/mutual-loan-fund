@extends('layouts.app')
@section('title', __('system.funds.index_title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('system.funds.index_title') }}</h1>
            <p class="muted small">
                {{ __('system.funds.index_subtitle') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('system.funds.table_fund') }}</th>
                    <th>{{ __('system.funds.table_status') }}</th>
                    <th>{{ __('system.funds.table_created_by') }}</th>
                    <th>{{ __('system.funds.table_members') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($funds as $fund)
                    <tr>
                        <td>
                            <a href="{{ route('s.funds.show', $fund) }}">{{ $fund->name }}</a>
                            <br><span class="small muted mono">{{ $fund->slug }}</span>
                        </td>
                        <td><x-status :value="$fund->status" /></td>
                        <td class="small muted">{{ $fund->creator?->name ?? '—' }}</td>
                        <td class="num">{{ $fund->active_memberships_count }}</td>
                        <td>
                            <div class="actions">
                                @if ($fund->status === \App\Models\Group::STATUS_SUSPENDED)
                                    <form method="POST" action="{{ route('s.funds.reinstate', $fund) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('system.funds.reinstate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('s.funds.suspend', $fund) }}">
                                        @csrf
                                        <button class="btn btn-small btn-danger">{{ __('system.funds.suspend') }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">{{ __('system.funds.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $funds->links('pagination') }}
    </div>
@endsection
