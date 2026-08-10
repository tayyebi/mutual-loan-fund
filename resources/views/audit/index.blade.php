@extends('layouts.app')
@section('title', __('audit.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('audit.index.title') }}</h1>
            <p class="muted small">
                {{ __('audit.index.subtitle') }}
            </p>
        </div>
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="action">{{ __('audit.index.filter_label') }}</label>
            <input id="action" name="action" type="text" value="{{ $filters['action'] ?? '' }}" placeholder="loan.">
        </div>
        <button class="btn">{{ __('audit.index.filter_button') }}</button>
        <a class="btn" href="{{ route('g.audit.index', $group) }}">{{ __('audit.index.clear') }}</a>
    </form>

    <div class="grid grid-side">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('audit.index.table_when') }}</th>
                        <th>{{ __('audit.index.table_actor') }}</th>
                        <th>{{ __('audit.index.table_action') }}</th>
                        <th>{{ __('audit.index.table_object') }}</th>
                        <th>{{ __('audit.index.table_detail') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small num"><x-datetime :value="$log->created_at" format="j M Y H:i" /></td>
                            <td class="small">{{ $log->actor?->name ?? __('audit.index.system_actor') }}</td>
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
                        <x-empty colspan="5">{{ __('audit.index.empty') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links('pagination') }}
        </div>

        <div class="card">
            <h2>{{ __('audit.index.policy_changes_title') }}</h2>
            <p class="small muted">{{ __('audit.index.policy_changes_subtitle') }}</p>

            @forelse ($policyEvents as $event)
                <p class="small" style="margin-bottom:0.5rem">
                    <strong>v{{ $event->version }}</strong> {{ $event->action }}
                    <br><span class="muted">
                        {{ $event->actor?->name ?? __('audit.index.system_actor') }} · <x-datetime :value="$event->created_at" format="j M Y H:i" />
                    </span>
                </p>
            @empty
                <p class="small muted">{{ __('audit.index.policy_changes_empty') }}</p>
            @endforelse
        </div>
    </div>
@endsection
