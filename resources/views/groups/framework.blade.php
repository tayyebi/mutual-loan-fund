@extends('layouts.app')
@section('title', 'Financial framework')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">Policies</a></p>
            <h1>Financial framework</h1>
            <p class="muted small">
                A named preset of advisory rules. It never blocks anything: if your
                policy drifts from it, you'll see a warning on the policy screens.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="{{ route('g.framework.update', $group) }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="financial_framework_id">Framework</label>
                    <select id="financial_framework_id" name="financial_framework_id">
                        <option value="" @selected($group->financial_framework_id === null)>None</option>
                        @foreach ($frameworks as $framework)
                            <option value="{{ $framework->id }}" @selected($group->financial_framework_id === $framework->id)>
                                {{ $framework->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('financial_framework_id')
                    <p class="hint error">{{ $message }}</p>
                @enderror

                <button class="btn btn-primary">Save</button>
            </form>
        </div>

        <div class="card">
            <h3>Available frameworks</h3>
            <dl class="deflist">
                @foreach ($frameworks as $framework)
                    <dt>{{ $framework->name }}</dt>
                    <dd>{{ $framework->description }}</dd>
                @endforeach
            </dl>
        </div>
    </div>
@endsection
