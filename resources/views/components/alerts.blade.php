@if (session('status'))
    <div class="alert alert-ok">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            The following problems need attention:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
