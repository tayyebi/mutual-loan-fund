@extends('layouts.app')
@section('title', 'Create a fund')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('home') }}">Your funds</a></p>
            <h1>Create a fund</h1>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="{{ route('groups.store') }}">
                @csrf

                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">{{ old('description') }}</textarea>
                </div>

                <div class="field">
                    <label for="financial_framework_id">Financial framework <span class="muted">(optional)</span></label>
                    <select id="financial_framework_id" name="financial_framework_id">
                        <option value="" @selected(old('financial_framework_id') === null)>None</option>
                        @foreach ($frameworks as $framework)
                            <option value="{{ $framework->id }}" @selected((string) old('financial_framework_id') === (string) $framework->id)>
                                {{ $framework->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('financial_framework_id')
                    <p class="hint error">{{ $message }}</p>
                @enderror
                <p class="hint">
                    A named preset of advisory rules. It never blocks anything — if your
                    fund's policy later drifts from it, you'll just see a warning. You can
                    change or clear it any time from Policies.
                </p>

                <button type="submit" class="btn btn-primary">Create fund</button>
            </form>
        </div>

        <div class="stack">
            <div class="card">
                <h3>What is created with it</h3>
                <ul class="small muted" style="padding-left: 1.1rem; margin: 0;">
                    <li>A chart of accounts for this fund alone.</li>
                    <li>Your administrator membership and cost center.</li>
                    <li>Policy v1, published and active.</li>
                </ul>
                <p class="small muted" style="margin-top: 0.8rem;">
                    All of it in one database transaction, so the fund is never half-created.
                </p>
            </div>

            <div class="card">
                <h3>Financial frameworks</h3>
                <dl class="deflist">
                    @foreach ($frameworks as $framework)
                        <dt>{{ $framework->name }}</dt>
                        <dd>{{ $framework->description }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
@endsection
