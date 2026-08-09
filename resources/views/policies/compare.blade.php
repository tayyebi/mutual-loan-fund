@extends('layouts.app')
@section('title', "Compare v{$from->version} with v{$to->version}")

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">Policies</a></p>
            <h1>v{{ $from->version }} → v{{ $to->version }}</h1>
            <p class="muted small">
                An administrative comparison. It changes nothing and touches no accounting.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Setting</th>
                    <th>v{{ $from->version }}</th>
                    <th>v{{ $to->version }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($changes as $change)
                    <tr>
                        <td>
                            {{ $change['field'] }}
                            <br><span class="small muted">{{ $change['category'] }}</span>
                        </td>
                        <td class="muted">{{ $change['from'] }}</td>
                        <td><strong>{{ $change['to'] }}</strong></td>
                    </tr>
                @empty
                    <x-empty colspan="3">These versions are identical.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
