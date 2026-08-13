@extends('layouts.app')
@section('title', __('wallets.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('wallets.index.heading') }}</h1>
            <p class="muted small">
                {{ __('wallets.index.intro') }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <h2>{{ __('wallets.index.your_wallets_heading') }}</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('wallets.index.col_label') }}</th>
                        <th>{{ __('wallets.index.col_network') }}</th>
                        <th>{{ __('wallets.index.col_address') }}</th>
                        <th>{{ __('wallets.index.col_status') }}</th>
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
                                <form method="POST" action="{{ route('u.wallets.update', [$group, $wallet]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="label" value="{{ $wallet->label }}">
                                    <input type="hidden" name="status"
                                           value="{{ $wallet->status === 'active' ? 'inactive' : 'active' }}">
                                    <button class="btn btn-small">
                                        {{ $wallet->status === 'active' ? __('wallets.index.deactivate_button') : __('wallets.index.activate_button') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty colspan="5">{{ __('wallets.index.empty') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('wallets.index.register_heading') }}</h2>
            <p class="small muted">{{ __('wallets.index.register_intro') }}</p>
            <a class="btn btn-primary" href="{{ route('u.wallets.register', $group) }}">{{ __('wallets.index.register_button') }}</a>
        </div>
    </div>

    @if ($groupWallets->isNotEmpty())
        <div class="card" style="margin-top: 1rem;">
            <h2>{{ __('wallets.index.all_wallets_heading') }}</h2>
            <p class="small muted">{{ __('wallets.index.all_wallets_intro') }}</p>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('wallets.index.col_member') }}</th>
                        <th>{{ __('wallets.index.col_network') }}</th>
                        <th>{{ __('wallets.index.col_address') }}</th>
                        <th>{{ __('wallets.index.col_status') }}</th>
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
