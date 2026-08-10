@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="grid grid-side">
        <div class="card">
            <p class="figure-sub">{{ __('reports.gold.fund_assets') }}</p>
            <p class="figure">{{ $valuation['grams']->format(4) }}</p>
            <p class="small muted">{{ __('reports.gold.grams_of_gold', ['unit' => $goldUnit]) }}</p>

            @if ($valuation['unvalued_lines'] > 0)
                <div class="alert alert-warn">
                    {{ __('reports.gold.unvalued_warning', ['count' => $valuation['unvalued_lines']]) }}
                </div>
            @endif

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('reports.gold.table_treasury') }}</th>
                        <th class="num">{{ __('reports.gold.table_native_balance') }}</th>
                        <th class="num">{{ __('reports.gold.table_gold_today') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($valuation['treasuries'] as $row)
                        <tr>
                            <td>{{ $row['treasury']->name }}</td>
                            <td class="num"><x-amount :value="$row['balance']" /></td>
                            <td class="num">
                                @if ($row['gold'])
                                    {{ $row['gold']->format(4) }} {{ __('reports.common.grams_suffix') }}
                                @else
                                    <span class="muted">{{ __('reports.common.no_rate') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty colspan="3">{{ __('reports.gold.empty') }}</x-empty>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>{{ __('reports.gold.two_figures_title') }}</h3>
            <p class="small muted">
                {{ __('reports.gold.note_headline') }}
            </p>
            <p class="small muted">
                {{ __('reports.gold.note_treasury_column') }}
            </p>
        </div>
    </div>
@endsection
