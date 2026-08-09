@extends('layouts.app')
@section('title', 'Publish policy v'.$policy->version)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">Policies</a></p>
            <h1>You are publishing policy v{{ $policy->version }}</h1>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <h2>Changes</h2>

            @if ($changes === [])
                <p class="muted">This draft makes no changes to the active policy.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Now</th>
                            <th>After publishing</th>
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
                This policy will apply to operations created after publication.
                Existing loans and transactions remain governed by their original
                policy versions.
            </div>

            @if ($errors_from_validator !== [])
                <div class="alert alert-error">
                    This draft cannot be published yet:
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
                            I understand that v{{ $policy->version }} becomes the active policy
                            @if ($active) and v{{ $active->version }} is superseded @endif.
                        </label>
                    </div>
                    <button class="btn btn-primary">Publish version {{ $policy->version }}</button>
                </form>
            @endif
        </div>

        <div class="card">
            <h3>What publishing does</h3>
            <ol class="small muted" style="padding-left:1.1rem;margin:0">
                <li>Validates the complete policy.</li>
                <li>Closes the current active version.</li>
                <li>Makes v{{ $policy->version }} active from today.</li>
                <li>Records who published it, and when.</li>
                <li>Writes an audit event.</li>
            </ol>
            <p class="small muted" style="margin-top:0.8rem">
                All of it in one database transaction: the fund is never left with two
                active versions, or none.
            </p>
        </div>
    </div>
@endsection
