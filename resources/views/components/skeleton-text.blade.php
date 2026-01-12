{{-- Skeleton Text Loading Component --}}
@props(['lines' => 3, 'spacing' => 'normal'])

@php
    $spacingClass = match($spacing) {
        'tight' => 'space-y-2',
        'normal' => 'space-y-3',
        'loose' => 'space-y-4',
        default => 'space-y-3'
    };
@endphp

<div {{ $attributes->merge(['class' => $spacingClass]) }}>
    @for ($i = 0; $i < $lines; $i++)
        @if ($i === $lines - 1)
            {{-- Last line is typically shorter --}}
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(50, 70) }}%;"></div>
        @else
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(85, 100) }}%;"></div>
        @endif
    @endfor
</div>
