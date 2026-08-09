@extends('layouts.app')
@section('title', 'Wallets')

@section('content')
    <div class="page-head">
        <div>
            <h1>Wallets</h1>
            <p class="muted small">
                External addresses only. This application is non-custodial: it never
                asks for, and cannot store, a private key or seed phrase.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <h2>Your wallets</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Label</th>
                        <th>Network</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($wallets as $wallet)
                        <tr>
                            <td>{{ $wallet->label ?? '—' }}<br><span class="small muted">{{ $wallet->currency }}</span></td>
                            <td>{{ $wallet->network }}</td>
                            <td class="mono small">
                                @if ($wallet->explorerUrl())
                                    <a href="{{ $wallet->explorerUrl() }}" rel="noreferrer noopener" target="_blank">{{ $wallet->maskedAddress() }}</a>
                                @else
                                    {{ $wallet->maskedAddress() }}
                                @endif
                            </td>
                            <td><x-status :value="$wallet->status" /></td>
                            <td>
                                <form method="POST" action="{{ route('g.wallets.update', [$group, $wallet]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="label" value="{{ $wallet->label }}">
                                    <input type="hidden" name="status"
                                           value="{{ $wallet->status === 'active' ? 'inactive' : 'active' }}">
                                    <button class="btn btn-small">
                                        {{ $wallet->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty colspan="5">You have not registered a wallet yet.</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Register a wallet</h2>
            <form method="POST" action="{{ route('g.wallets.store', $group) }}">
                @csrf

                <div class="field">
                    <label for="network">Network</label>
                    <select id="network" name="network" required>
                        @foreach ($networks as $key => $network)
                            <option value="{{ $key }}" @selected(old('network') === $key)>{{ $network['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" required>
                        @foreach ($currencies as $code => $meta)
                            @continue($code === config('fund.gold_unit'))
                            <option value="{{ $code }}" @selected(old('currency', auth()->user()->preferred_currency ?? 'USDT') === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="address">Address</label>
                    <input id="address" name="address" type="text" value="{{ old('address') }}" required>
                    <span class="hint">The public address only.</span>
                </div>

                <div class="field">
                    <label for="label">Label</label>
                    <input id="label" name="label" type="text" value="{{ old('label') }}">
                </div>

                <button class="btn btn-primary">Register</button>
            </form>
        </div>
    </div>

    @if ($groupWallets->isNotEmpty())
        <div class="card" style="margin-top: 1rem;">
            <h2>All registered wallets</h2>
            <p class="small muted">Shown to administrators so incoming transfers can be recognised.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Member</th>
                        <th>Network</th>
                        <th>Address</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($groupWallets as $wallet)
                        <tr>
                            <td>{{ $wallet->member?->displayName() }}</td>
                            <td>{{ $wallet->network }}</td>
                            <td class="mono small">{{ $wallet->address }}</td>
                            <td><x-status :value="$wallet->status" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
