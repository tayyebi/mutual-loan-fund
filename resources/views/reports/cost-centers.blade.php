@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <p class="small muted">
            Activity grouped by who or what it belongs to. Open a cost center for its full
            statement.
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Member</th>
                    <th>Status</th>
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
                    <x-empty colspan="4">No cost centers.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
