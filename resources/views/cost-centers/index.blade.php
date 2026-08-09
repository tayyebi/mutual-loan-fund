@extends('layouts.app')
@section('title', 'Cost centers')

@section('content')
    <div class="page-head">
        <div>
            <h1>Cost centers</h1>
            <p class="muted small">
                A cost center owns no money. It says who or what an amount belongs to, and
                stays stable even when a member's name changes.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
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
                        <x-empty colspan="4">No cost centers yet.</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($groupContext->isAdmin())
            <div class="card">
                <h2>Add a cost center</h2>
                <p class="small muted">
                    Members get one automatically. Add others for activities or shared costs.
                </p>
                <form method="POST" action="{{ route('g.cost-centers.store', $group) }}">
                    @csrf
                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary">Create</button>
                </form>
            </div>
        @endif
    </div>
@endsection
