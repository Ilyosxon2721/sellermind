@props([
    'variant' => 'primary',
    'size' => 'default',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'loading' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variants = [
    'primary' => 'text-white bg-blue-600 border border-transparent shadow-sm hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'text-gray-700 bg-white border border-gray-300 shadow-sm hover:bg-gray-50 focus:ring-blue-500',
    'danger' => 'text-white bg-red-600 border border-transparent shadow-sm hover:bg-red-700 focus:ring-red-500',
    'success' => 'text-white bg-green-600 border border-transparent shadow-sm hover:bg-green-700 focus:ring-green-500',
    'ghost' => 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:ring-gray-500',
    'link' => 'text-blue-600 hover:text-blue-800 underline-offset-4 hover:underline focus:ring-blue-500',
];

$sizes = [
    'xs' => 'px-2.5 py-1.5 text-xs',
    'sm' => 'px-3 py-1.5 text-sm',
    'default' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp

@if($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}" 
        {{ $disabled || $loading ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => $classes]) }}
    >
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
