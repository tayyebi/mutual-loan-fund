@extends('layouts.app')
@section('title', 'Platform audit log')

@section('content')
    <div class="page-head">
        <div>
            <h1>Platform audit log</h1>
            <p class="muted small">
                System-administrator actions only — user suspensions, fund suspensions
                and admin grants. Each fund's own audit trail stays private to that
                fund's own administrators.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>When</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Object</th>
                    <th>Detail</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small num">{{ $log->created_at->format('j M Y H:i') }}</td>
                        <td class="small">{{ $log->actor?->name ?? 'system' }}</td>
                        <td class="small mono">{{ $log->action }}</td>
                        <td class="small muted">{{ $log->objectLabel() ?? '—' }}</td>
                        <td class="small muted">
                            @if ($log->new_values)
                                @foreach ($log->new_values as $key => $value)
                                    <span class="kv">{{ $key }}: {{ is_scalar($value) ? $value : json_encode($value) }}</span>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">No system-administrator actions yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links('pagination') }}
    </div>
@endsection
