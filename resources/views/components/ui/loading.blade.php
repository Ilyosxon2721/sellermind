@props([
    'size' => 'default',
    'color' => 'primary',
])

@php
$sizes = [
    'sm' => 'h-4 w-4',
    'default' => 'h-8 w-8',
    'lg' => 'h-12 w-12',
];

$colors = [
    'primary' => 'text-blue-600',
    'white' => 'text-white',
    'gray' => 'text-gray-400',
];
@endphp

<svg 
    class="animate-spin {{ $sizes[$size] }} {{ $colors[$color] }}" 
    fill="none" 
    viewBox="0 0 24 24"
    {{ $attributes }}
>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
</svg>
