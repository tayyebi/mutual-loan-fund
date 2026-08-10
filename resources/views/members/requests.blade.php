@extends('layouts.app')
@section('title', __('members.requests.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.members.index', $group) }}">{{ __('members.requests.breadcrumb') }}</a></p>
            <h1>{{ __('members.requests.heading') }}</h1>
            <p class="muted small">{{ __('members.requests.intro') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('members.requests.person_header') }}</th>
                    <th>{{ __('members.requests.requested_header') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td>
                            {{ $request->displayName() }}
                            <br><span class="small muted">{{ $request->user?->email }}</span>
                        </td>
                        <td class="small muted">
                            @if ($request->requested_at)<x-datetime :value="$request->requested_at" />@endif
                        </td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('g.members.approve', [$group, $request]) }}">
                                    @csrf
                                    <button class="btn btn-small btn-primary">{{ __('members.requests.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('g.members.reject', [$group, $request]) }}" class="actions">
                                    @csrf
                                    <input type="text" name="reason" placeholder="{{ __('members.requests.reason_placeholder') }}" class="small">
                                    <button class="btn btn-small btn-danger">{{ __('members.requests.reject') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="3">{{ __('members.requests.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
