@props(['colspan' => 1])

<tr>
    <td colspan="{{ $colspan }}" class="table-empty">{{ $slot }}</td>
</tr>
