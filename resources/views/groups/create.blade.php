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

                <button type="submit" class="btn btn-primary">Create fund</button>
            </form>
        </div>

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
    </div>
@endsection
