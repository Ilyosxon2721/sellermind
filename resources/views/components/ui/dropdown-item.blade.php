@props([
    'href' => null,
    'danger' => false,
])

@php
$classes = $danger 
    ? 'block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 transition-colors'
    : 'block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition-colors';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        {{ $slot }}
    </button>
@endif
