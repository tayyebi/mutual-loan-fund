@extends('layouts.app')
@section('title', __('cost_centers.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('cost_centers.index.title') }}</h1>
            <p class="muted small">
                {{ __('cost_centers.index.subtitle') }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('cost_centers.index.table_code') }}</th>
                        <th>{{ __('cost_centers.index.table_name') }}</th>
                        <th>{{ __('cost_centers.index.table_member') }}</th>
                        <th>{{ __('cost_centers.index.table_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($costCenters as $costCenter)
                        <tr>
                            <td class="mono">
                                <a href="{{ route('g.cost-centers.show', [$group, $costCenter]) }}">{{ $costCenter->code }}</a>
                            </td>
                            <td>{{ $costCenter->name }}</td>
                            <td class="small muted">{{ $costCenter->member?->displayName() ?? '—' }}</td>
                            <td><x-status :value="$costCenter->status" /></td>
                        </tr>
                    @empty
                        <x-empty colspan="4">{{ __('cost_centers.index.empty') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($groupContext->isAdmin())
            <div class="card">
                <h2>{{ __('cost_centers.index.add_title') }}</h2>
                <p class="small muted">
                    {{ __('cost_centers.index.add_subtitle') }}
                </p>
                <form method="POST" action="{{ route('g.cost-centers.store', $group) }}">
                    @csrf
                    <div class="field">
                        <label for="name">{{ __('cost_centers.index.field_name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="description">{{ __('cost_centers.index.field_description') }}</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary">{{ __('cost_centers.index.create') }}</button>
                </form>
            </div>
        @endif
    </div>
@endsection
