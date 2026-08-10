@extends('layouts.app')
@section('title', __('admin.audit.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('admin.audit.title') }}</h1>
            <p class="muted small">
                {{ __('admin.audit.subtitle') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('admin.audit.table_when') }}</th>
                    <th>{{ __('admin.audit.table_actor') }}</th>
                    <th>{{ __('admin.audit.table_action') }}</th>
                    <th>{{ __('admin.audit.table_object') }}</th>
                    <th>{{ __('admin.audit.table_detail') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small num"><x-datetime :value="$log->created_at" format="j M Y H:i" /></td>
                        <td class="small">{{ $log->actor?->name ?? __('admin.audit.system_actor') }}</td>
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
                    <x-empty colspan="5">{{ __('admin.audit.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links('pagination') }}
    </div>
@endsection
