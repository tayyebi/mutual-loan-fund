@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <p class="small muted">
            {{ __('reports.cost_centers.subtitle') }}
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('reports.cost_centers.table_code') }}</th>
                    <th>{{ __('reports.cost_centers.table_name') }}</th>
                    <th>{{ __('reports.cost_centers.table_member') }}</th>
                    <th>{{ __('reports.cost_centers.table_status') }}</th>
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
                    <x-empty colspan="4">{{ __('reports.cost_centers.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
