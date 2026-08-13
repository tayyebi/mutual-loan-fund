@props(['steps', 'current'])

{{--
    A plain "step 2 of 3" progress row for the member-facing money/loan
    wizards — numbered dots connected by a line, no JS. $steps is a list of
    translated labels; $current is 1-indexed.
--}}
<div class="step-progress" role="list" aria-label="{{ __('wizard.progress_label', ['current' => $current, 'total' => count($steps)]) }}">
    @foreach ($steps as $index => $label)
        @php($n = $index + 1)
        <div class="step-progress-item @if ($n < $current) is-done @elseif ($n === $current) is-current @endif" role="listitem">
            <span class="step-progress-dot">{{ $n }}</span>
            <span class="step-progress-label">{{ $label }}</span>
        </div>
    @endforeach
</div>
