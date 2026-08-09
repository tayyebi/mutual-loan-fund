@extends('layouts.app')
@section('title', 'Audit log')

@section('content')
    <div class="page-head">
        <div>
            <h1>Audit log</h1>
            <p class="muted small">
                Append-only. Each entry is written in the same database transaction as the
                action it records, so a committed action always has its trace.
            </p>
        </div>
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="action">Action starts with</label>
            <input id="action" name="action" type="text" value="{{ $filters['action'] ?? '' }}" placeholder="loan.">
        </div>
        <button class="btn">Filter</button>
        <a class="btn" href="{{ route('g.audit.index', $group) }}">Clear</a>
    </form>

    <div class="grid grid-side">
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
                        <x-empty colspan="5">No audit entries match.</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links('pagination') }}
        </div>

        <div class="card">
            <h2>Policy changes</h2>
            <p class="small muted">Kept separately, with the full configuration before and after.</p>

            @forelse ($policyEvents as $event)
                <p class="small" style="margin-bottom:0.5rem">
                    <strong>v{{ $event->version }}</strong> {{ $event->action }}
                    <br><span class="muted">
                        {{ $event->actor?->name ?? 'system' }} · {{ $event->created_at->format('j M Y H:i') }}
                    </span>
                </p>
            @empty
                <p class="small muted">No policy changes recorded.</p>
            @endforelse
        </div>
    </div>
@endsection
