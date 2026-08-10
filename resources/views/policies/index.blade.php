@extends('layouts.app')
@section('title', __('policies.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('policies.index.heading') }}</h1>
            <p class="muted small">
                {{ __('policies.index.intro') }}
            </p>
        </div>

        @unless ($draft)
            <form method="POST" action="{{ route('g.policies.store', $group) }}">
                @csrf
                <button class="btn btn-primary">{{ __('policies.index.new_draft') }}</button>
            </form>
        @else
            <a class="btn btn-primary" href="{{ route('g.policies.edit', [$group, $draft->version]) }}">
                {{ __('policies.index.continue_draft', ['version' => $draft->version]) }}
            </a>
        @endunless
    </div>

    <div class="card">
        <div class="page-head" style="margin:0">
            <div>
                <h3 style="margin:0">{{ __('policies.index.framework_heading') }}</h3>
                <p class="muted small" style="margin:0">
                    {{ $group->financialFramework?->name ?? __('policies.index.framework_none') }}
                </p>
            </div>
            <a class="btn btn-small" href="{{ route('g.framework.edit', $group) }}">{{ __('policies.index.framework_change') }}</a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('policies.index.version_header') }}</th>
                    <th>{{ __('policies.index.status_header') }}</th>
                    <th>{{ __('policies.index.effective_from_header') }}</th>
                    <th>{{ __('policies.index.effective_until_header') }}</th>
                    <th>{{ __('policies.index.published_by_header') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($versions as $version)
                    <tr>
                        <td class="mono">
                            <a href="{{ route('g.policies.show', [$group, $version->version]) }}">v{{ $version->version }}</a>
                        </td>
                        <td>
                            @if ($version->isActive())
                                <span class="badge badge-ok">{{ __('policies.index.active_badge') }}</span>
                            @else
                                <x-status :value="$version->status" />
                            @endif
                        </td>
                        <td class="num small">
                            @if ($version->effective_from)<x-datetime :value="$version->effective_from" />@else —@endif
                        </td>
                        <td class="num small">
                            @if ($version->effective_until)<x-datetime :value="$version->effective_until" />@else —@endif
                        </td>
                        <td class="small muted">{{ $version->publisher?->name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                @if ($version->isDraft())
                                    <a class="btn btn-small" href="{{ route('g.policies.edit', [$group, $version->version]) }}">{{ __('policies.index.edit_link') }}</a>
                                    <a class="btn btn-small btn-primary" href="{{ route('g.policies.publish.confirm', [$group, $version->version]) }}">{{ __('policies.index.publish_link') }}</a>
                                @elseif ($active && $version->version !== $active->version)
                                    <a class="btn btn-small" href="{{ route('g.policies.compare', [$group, $active->version, $version->version]) }}">
                                        {{ __('policies.index.compare_link') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="6">{{ __('policies.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
