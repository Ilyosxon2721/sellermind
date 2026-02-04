@props([
    'align' => 'right',
    'width' => '48',
])

@php
$alignmentClasses = [
    'left' => 'origin-top-left left-0',
    'right' => 'origin-top-right right-0',
];

$widthClasses = [
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
];
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $widthClasses[$width] }} {{ $alignmentClasses[$align] }}"
        style="display: none;"
    >
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 py-1">
            {{ $slot }}
        </div>
    </div>
</div>
