@props(['colspan' => 1, 'as' => 'table'])

@if ($as === 'list')
    <div class="table-empty">{{ $slot }}</div>
@else
    <tr>
        <td colspan="{{ $colspan }}" class="table-empty">{{ $slot }}</td>
    </tr>
@endif
