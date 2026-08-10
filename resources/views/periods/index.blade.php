@extends('layouts.app')
@section('title', __('periods.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('periods.index.title') }}</h1>
            <p class="muted small">
                {{ __('periods.index.subtitle') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('periods.index.table_period') }}</th>
                    <th>{{ __('periods.index.table_status') }}</th>
                    <th>{{ __('periods.index.table_closed') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td class="mono">{{ $period->label() }}</td>
                        <td><x-status :value="$period->status" /></td>
                        <td class="small muted">
                            @if ($period->closed_at)
                                {{ $period->closer?->name }} · <x-datetime :value="$period->closed_at" />
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($period->isClosed())
                                <form method="POST" action="{{ route('g.periods.reopen', [$group, $period]) }}">
                                    @csrf
                                    <button class="btn btn-small">{{ __('periods.index.reopen') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('g.periods.close', [$group, $period]) }}">
                                    @csrf
                                    <button class="btn btn-small">{{ __('periods.index.close') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="4">{{ __('periods.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            {{ __('periods.index.reopen_note') }}
        </p>
    </div>
@endsection
