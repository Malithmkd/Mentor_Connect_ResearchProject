{{-- Reusable progress bar component --}}
{{-- Usage: @include('partials.lms._progress_bar', ['percent' => $enrollment->progress_percentage, 'label' => '']) --}}
@props(['percent' => 0, 'label' => '', 'size' => 'md'])

@php
    $height = $size === 'sm' ? 'h-1.5' : ($size === 'lg' ? 'h-3' : 'h-2');
    $color  = $percent >= 100 ? 'bg-green-500' : ($percent >= 50 ? 'bg-indigo-500' : 'bg-indigo-400');
@endphp

<div class="lms-progress">
    @if($label)
        <div class="lms-progress__label">
            <span>{{ $label }}</span>
            <span class="lms-progress__pct">{{ $percent }}%</span>
        </div>
    @endif
    <div class="lms-progress__track {{ $height }}" role="progressbar"
         aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="lms-progress__fill {{ $color }}"
             style="width: {{ min($percent, 100) }}%"></div>
    </div>
</div>
