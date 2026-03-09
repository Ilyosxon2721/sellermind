{{-- PWA Notification Item Component --}}
{{-- Элемент уведомления для PWA Dashboard --}}

@props([
    'title' => '',
    'message' => '',
    'type' => 'info',
    'time' => null,
    'href' => null,
    'icon' => null,
    'unread' => false,
])

@php
$tag = $href ? 'a' : 'div';

// Цветовая схема по типу
$typeStyles = [
    'info' => [
        'bg' => 'bg-blue-50',
        'icon_bg' => 'bg-blue-100',
        'icon_color' => 'text-blue-600',
        'border' => 'border-blue-100',
    ],
    'success' => [
        'bg' => 'bg-green-50',
        'icon_bg' => 'bg-green-100',
        'icon_color' => 'text-green-600',
        'border' => 'border-green-100',
    ],
    'warning' => [
        'bg' => 'bg-amber-50',
        'icon_bg' => 'bg-amber-100',
        'icon_color' => 'text-amber-600',
        'border' => 'border-amber-100',
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'icon_bg' => 'bg-red-100',
        'icon_color' => 'text-red-600',
        'border' => 'border-red-100',
    ],
    'order' => [
        'bg' => 'bg-purple-50',
        'icon_bg' => 'bg-purple-100',
        'icon_color' => 'text-purple-600',
        'border' => 'border-purple-100',
    ],
    'review' => [
        'bg' => 'bg-yellow-50',
        'icon_bg' => 'bg-yellow-100',
        'icon_color' => 'text-yellow-600',
        'border' => 'border-yellow-100',
    ],
    'stock' => [
        'bg' => 'bg-orange-50',
        'icon_bg' => 'bg-orange-100',
        'icon_color' => 'text-orange-600',
        'border' => 'border-orange-100',
    ],
    'promo' => [
        'bg' => 'bg-indigo-50',
        'icon_bg' => 'bg-indigo-100',
        'icon_color' => 'text-indigo-600',
        'border' => 'border-indigo-100',
    ],
];

$styles = $typeStyles[$type] ?? $typeStyles['info'];

// Иконки по типу
$defaultIcons = [
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'order' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
    'review' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
    'stock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
    'promo' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];

$iconPath = $icon ?? ($defaultIcons[$type] ?? $defaultIcons['info']);
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" onclick="if(window.haptic) window.haptic.light()" @endif
    {{ $attributes->merge(['class' => "pwa-only sm-notification-item {$styles['bg']} border {$styles['border']}"]) }}
>
    {{-- Icon --}}
    <div class="sm-notification-icon {{ $styles['icon_bg'] }}">
        <svg class="w-5 h-5 {{ $styles['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $iconPath !!}
        </svg>
    </div>

    {{-- Content --}}
    <div class="sm-notification-content">
        <div class="sm-notification-header">
            <span class="sm-notification-title {{ $unread ? 'font-semibold' : '' }}">{{ $title }}</span>
            @if($time)
                <span class="sm-notification-time">{{ $time }}</span>
            @endif
        </div>
        @if($message)
            <p class="sm-notification-message">{{ $message }}</p>
        @endif
    </div>

    {{-- Unread indicator --}}
    @if($unread)
        <div class="sm-notification-unread"></div>
    @endif

    {{-- Chevron for clickable items --}}
    @if($href)
        <svg class="sm-notification-chevron w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    @endif
</{{ $tag }}>

<style>
/* Notification Item */
.pwa-mode .sm-notification-item {
    display: flex;
    align-items: flex-start;
    gap: var(--sm-space-md, 12px);
    padding: var(--sm-space-md, 12px);
    border-radius: var(--sm-radius-lg, 12px);
    text-decoration: none;
    color: inherit;
    transition: transform 0.15s, opacity 0.15s;
}

.pwa-mode .sm-notification-item:active {
    transform: scale(0.98);
    opacity: 0.9;
}

.pwa-mode .sm-notification-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--sm-radius-md, 10px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.pwa-mode .sm-notification-content {
    flex: 1;
    min-width: 0;
}

.pwa-mode .sm-notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sm-space-sm, 8px);
    margin-bottom: 2px;
}

.pwa-mode .sm-notification-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--sm-text-primary, #1f2937);
    line-height: 1.3;
}

.pwa-mode .sm-notification-time {
    font-size: 12px;
    color: var(--sm-text-tertiary, #9ca3af);
    flex-shrink: 0;
}

.pwa-mode .sm-notification-message {
    font-size: 13px;
    color: var(--sm-text-secondary, #6b7280);
    line-height: 1.4;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.pwa-mode .sm-notification-unread {
    width: 8px;
    height: 8px;
    background: var(--sm-primary, #3b82f6);
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 6px;
}

.pwa-mode .sm-notification-chevron {
    margin-top: 8px;
}

/* Compact variant */
.pwa-mode .sm-notification-item.compact {
    padding: var(--sm-space-sm, 8px) var(--sm-space-md, 12px);
}

.pwa-mode .sm-notification-item.compact .sm-notification-icon {
    width: 32px;
    height: 32px;
}

.pwa-mode .sm-notification-item.compact .sm-notification-icon svg {
    width: 16px;
    height: 16px;
}

.pwa-mode .sm-notification-item.compact .sm-notification-title {
    font-size: 13px;
}

.pwa-mode .sm-notification-item.compact .sm-notification-message {
    font-size: 12px;
    -webkit-line-clamp: 1;
}
</style>
