@props([
    'variant' => 'gray',
    'size' => 'default',
])

@php
$variants = [
    'gray' => 'bg-gray-100 text-gray-800',
    'primary' => 'bg-blue-100 text-blue-800',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800',
    'info' => 'bg-cyan-100 text-cyan-800',
    // Marketplaces
    'wildberries' => 'bg-pink-100 text-pink-800',
    'ozon' => 'bg-blue-100 text-blue-800',
    'uzum' => 'bg-purple-100 text-purple-800',
    'yandex' => 'bg-yellow-100 text-yellow-800',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'default' => 'px-2.5 py-0.5 text-xs',
    'lg' => 'px-3 py-1 text-sm',
];

$classes = "inline-flex items-center font-medium rounded-full {$variants[$variant]} {$sizes[$size]}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
