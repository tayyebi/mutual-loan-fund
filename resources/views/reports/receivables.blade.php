@extends('layouts.app')
@section('title', $title)

@section('content')
    @include('reports.partials.head')

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('reports.receivables.table_cost_center') }}</th>
                    <th>{{ __('reports.receivables.table_member') }}</th>
                    <th class="num">{{ __('reports.receivables.table_outstanding_principal') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="mono">
                            <a href="{{ route('g.cost-centers.show', [$group, $row['cost_center']]) }}">
                                {{ $row['cost_center']->code }}
                            </a>
                        </td>
                        <td>{{ $row['cost_center']->name }}</td>
                        <td class="num">
                            @foreach ($row['balances'] as $currency => $balance)
                                <x-amount :value="$balance" :currency="$currency" />@if (! $loop->last)<br>@endif
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="3">{{ __('reports.receivables.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            {{ __('reports.receivables.note') }}
        </p>
    </div>
@endsection
