@extends('layouts.app')
@section('title', __('policies.publish.title', ['version' => $policy->version]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">{{ __('policies.publish.breadcrumb') }}</a></p>
            <h1>{{ __('policies.publish.heading', ['version' => $policy->version]) }}</h1>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <h2>{{ __('policies.publish.changes_heading') }}</h2>

            @if ($changes === [])
                <p class="muted">{{ __('policies.publish.no_changes') }}</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('policies.publish.setting_header') }}</th>
                            <th>{{ __('policies.publish.now_header') }}</th>
                            <th>{{ __('policies.publish.after_header') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($changes as $change)
                            <tr>
                                <td>
                                    {{ $change['field'] }}
                                    <br><span class="small muted">{{ $change['category'] }}</span>
                                </td>
                                <td class="muted">{{ $change['from'] }}</td>
                                <td><strong>{{ $change['to'] }}</strong></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="alert alert-warn" style="margin-top:1rem">
                {{ __('policies.publish.applies_note') }}
            </div>

            @if ($framework_warnings !== [])
                <div class="alert alert-warn" style="margin-top:1rem">
                    {{ __('policies.publish.framework_drift_heading') }}
                    <ul>
                        @foreach ($framework_warnings as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors_from_validator !== [])
                <div class="alert alert-error">
                    {{ __('policies.publish.blocked_heading') }}
                    <ul>
                        @foreach ($errors_from_validator as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <form method="POST" action="{{ route('g.policies.publish', [$group, $policy->version]) }}">
                    @csrf
                    <div class="field check">
                        <input id="confirm" name="confirm" type="checkbox" value="1" required>
                        <label for="confirm">
                            {{ __('policies.publish.confirm_line', ['version' => $policy->version]) }}
                            @if ($active) {{ __('policies.publish.confirm_superseded', ['active_version' => $active->version]) }} @endif.
                        </label>
                    </div>
                    <button class="btn btn-primary">{{ __('policies.publish.submit', ['version' => $policy->version]) }}</button>
                </form>
            @endif
        </div>

        <div class="card">
            <h3>{{ __('policies.publish.what_happens_heading') }}</h3>
            <ol class="small muted" style="padding-left:1.1rem;margin:0">
                <li>{{ __('policies.publish.step_validates') }}</li>
                <li>{{ __('policies.publish.step_closes') }}</li>
                <li>{{ __('policies.publish.step_makes_active', ['version' => $policy->version]) }}</li>
                <li>{{ __('policies.publish.step_records') }}</li>
                <li>{{ __('policies.publish.step_audit') }}</li>
            </ol>
            <p class="small muted" style="margin-top:0.8rem">
                {{ __('policies.publish.transaction_note') }}
            </p>
        </div>
    </div>
@endsection
