@props([
    'title' => null,
    'text' => null,
    'url' => null,
    'label' => 'Поделиться',
    'icon' => true,
    'variant' => 'default', // default, primary, ghost, icon-only
    'size' => 'md', // sm, md, lg
])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-medium transition-colors rounded-xl';

$variantClasses = [
    'default' => 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100',
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800',
    'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100 active:bg-gray-200',
    'icon-only' => 'bg-transparent text-gray-600 hover:bg-gray-100 active:bg-gray-200 rounded-full',
];

$sizeClasses = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-2.5 text-base',
    'lg' => 'px-6 py-3 text-lg',
];

$iconSizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];

$classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['default']);

if ($variant !== 'icon-only') {
    $classes .= ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
} else {
    $classes .= ' p-2';
}

$iconSize = $iconSizeClasses[$size] ?? $iconSizeClasses['md'];

$shareData = json_encode([
    'title' => $title,
    'text' => $text,
    'url' => $url ?? url()->current(),
]);
@endphp

<button
    {{ $attributes->merge(['class' => $classes]) }}
    x-data
    @click="$share.share({{ $shareData }})"
    data-haptic="light"
    type="button">
    @if($icon)
        <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
    @endif

    @if($variant !== 'icon-only')
        <span>{{ $label }}</span>
    @endif
</button>
