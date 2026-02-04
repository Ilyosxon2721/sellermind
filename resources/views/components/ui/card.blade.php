@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'default',
    'hover' => false,
])

@php
$paddings = [
    'none' => '',
    'sm' => 'p-4',
    'default' => 'p-6',
    'lg' => 'p-8',
];

$hoverClass = $hover ? 'hover:shadow-md hover:border-gray-300 transition-all cursor-pointer' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden {$hoverClass}"]) }}>
    @if($title)
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
            @if($subtitle)
                <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="{{ $paddings[$padding] }}">
        {{ $slot }}
    </div>
    
    @isset($footer)
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $footer }}
        </div>
    @endisset
</div>
