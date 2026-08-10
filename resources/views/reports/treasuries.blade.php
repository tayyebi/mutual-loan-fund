@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('reports.treasuries.table_treasury') }}</th>
                    <th>{{ __('reports.treasuries.table_type') }}</th>
                    <th>{{ __('reports.treasuries.table_held_at') }}</th>
                    <th class="num">{{ __('reports.treasuries.table_native_balance') }}</th>
                    <th class="num">{{ __('reports.treasuries.table_gold') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['treasury']->name }}</td>
                        <td class="small muted">{{ $row['treasury']->type }} · {{ $row['treasury']->network ?? __('reports.common.bank_fallback') }}</td>
                        <td class="mono small truncate">{{ $row['treasury']->external_identifier ?? '—' }}</td>
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
                    <x-empty colspan="5">{{ __('reports.treasuries.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            {{ __('reports.treasuries.note') }}
        </p>
    </div>
@endsection
