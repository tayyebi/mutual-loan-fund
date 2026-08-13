@props(['title', 'backHref' => null, 'backLabel' => null, 'steps' => null, 'current' => null])

{{--
    The one-question-per-page shell shared by the member money/loan wizards
    (resources/views/member/wizard/*). Keeps every step to the same page-head
    + progress + single-card layout so the individual step views only need to
    supply the question itself.
--}}
<div class="page-head">
    <div>
        @if ($backHref)
            <p class="breadcrumb"><a href="{{ $backHref }}">{{ $backLabel ?? __('wizard.back') }}</a></p>
        @endif
        <h1>{{ $title }}</h1>
    </div>
</div>

@if ($steps)
    <x-step-progress :steps="$steps" :current="$current" />
@endif

<div class="card wizard-card">
    {{ $slot }}
</div>
