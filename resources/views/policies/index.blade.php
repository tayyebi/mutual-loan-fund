@extends('layouts.app')
@section('title', 'Policies')

@section('content')
    <div class="page-head">
        <div>
            <h1>Policies</h1>
            <p class="muted small">
                Financial rules are versioned. A published version is immutable; changing
                the rules means publishing a new version, and existing operations keep
                the version they were created under.
            </p>
        </div>

        @unless ($draft)
            <form method="POST" action="{{ route('g.policies.store', $group) }}">
                @csrf
                <button class="btn btn-primary">New draft</button>
            </form>
        @else
            <a class="btn btn-primary" href="{{ route('g.policies.edit', [$group, $draft->version]) }}">
                Continue draft v{{ $draft->version }}
            </a>
        @endunless
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Effective from</th>
                    <th>Effective until</th>
                    <th>Published by</th>
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
                                <span class="badge badge-ok">Active</span>
                            @else
                                <x-status :value="$version->status" />
                            @endif
                        </td>
                        <td class="num small">{{ $version->effective_from?->format('j M Y') ?? '—' }}</td>
                        <td class="num small">{{ $version->effective_until?->format('j M Y') ?? '—' }}</td>
                        <td class="small muted">{{ $version->publisher?->name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                @if ($version->isDraft())
                                    <a class="btn btn-small" href="{{ route('g.policies.edit', [$group, $version->version]) }}">Edit</a>
                                    <a class="btn btn-small btn-primary" href="{{ route('g.policies.publish.confirm', [$group, $version->version]) }}">Publish</a>
                                @elseif ($active && $version->version !== $active->version)
                                    <a class="btn btn-small" href="{{ route('g.policies.compare', [$group, $active->version, $version->version]) }}">
                                        Compare with active
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="6">No policy versions yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
