{{-- PWA Stat Widget Component --}}
{{-- Компактный виджет метрики для PWA Dashboard --}}

@props([
    'title' => 'Метрика',
    'value' => '0',
    'icon' => 'chart-bar',
    'trend' => null,
    'trendDirection' => 'neutral',
    'color' => 'blue',
    'size' => 'half',
    'loading' => false,
    'animate' => false,
])

@php
// Цветовая схема для иконки
$iconColors = [
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'red' => 'bg-red-100 text-red-600',
    'purple' => 'bg-purple-100 text-purple-600',
    'yellow' => 'bg-yellow-100 text-yellow-600',
    'orange' => 'bg-orange-100 text-orange-600',
    'pink' => 'bg-pink-100 text-pink-600',
    'indigo' => 'bg-indigo-100 text-indigo-600',
];

$iconColorClass = $iconColors[$color] ?? $iconColors['blue'];

// Цвет тренда
$trendColors = [
    'up' => 'text-green-600 bg-green-50',
    'down' => 'text-red-600 bg-red-50',
    'neutral' => 'text-gray-500 bg-gray-100',
];

$trendColorClass = $trendColors[$trendDirection] ?? $trendColors['neutral'];

// Размер карточки
$sizeClass = match($size) {
    'half' => 'w-[calc(50%-8px)]',
    'full' => 'w-full',
    default => 'w-[calc(50%-8px)]',
};

// Иконки Heroicons (inline SVG для производительности)
$icons = [
    'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'shopping-bag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
    'shopping-cart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'star' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'trending-up' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
    'trending-down' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>',
    'cube' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
    'eye' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
    'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
    'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'truck' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
    'credit-card' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
    'receipt-refund' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>',
    'exclamation' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'percentage' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
];

$selectedIcon = $icons[$icon] ?? $icons['chart-bar'];
@endphp

<div
    {{ $attributes->merge(['class' => "flex-shrink-0 {$sizeClass}"]) }}
    @if($animate)
    x-data="{
        displayValue: '0',
        targetValue: @js($value),
        init() {
            this.animateValue();
        },
        animateValue() {
            const target = parseFloat(this.targetValue.toString().replace(/[^\d.-]/g, '')) || 0;
            const prefix = this.targetValue.toString().match(/^[^\d-]*/)?.[0] || '';
            const suffix = this.targetValue.toString().match(/[^\d.]*$/)?.[0] || '';
            const duration = 1000;
            const start = performance.now();

            const animate = (currentTime) => {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(target * easeOut);

                this.displayValue = prefix + current.toLocaleString('ru-RU') + suffix;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.displayValue = this.targetValue;
                }
            };
            requestAnimationFrame(animate);
        }
    }"
    @endif
>
    {{-- Loading State --}}
    @if($loading)
    <div class="bg-white rounded-2xl p-4 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            {{-- Icon skeleton --}}
            <div class="w-10 h-10 bg-gray-200 rounded-xl skeleton"></div>
            {{-- Trend skeleton --}}
            <div class="w-12 h-5 bg-gray-200 rounded-full skeleton"></div>
        </div>
        {{-- Title skeleton --}}
        <div class="h-3.5 bg-gray-200 rounded skeleton mb-2" style="width: 60%;"></div>
        {{-- Value skeleton --}}
        <div class="h-6 bg-gray-200 rounded skeleton" style="width: 80%;"></div>
    </div>
    @else
    {{-- Actual Widget --}}
    <div
        class="bg-white rounded-2xl p-4 shadow-sm transition-all duration-200 active:scale-[0.98]"
        style="animation: fadeSlideUp 0.3s ease-out both; animation-delay: {{ ($attributes->get('index', 0) ?? 0) * 0.05 }}s;"
    >
        {{-- Header: Icon + Trend --}}
        <div class="flex items-start justify-between mb-3">
            {{-- Icon --}}
            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $iconColorClass }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {{-- Intentional: $selectedIcon is from hardcoded $icons array --}}
                    {!! $selectedIcon !!}
                </svg>
            </div>

            {{-- Trend Badge --}}
            @if($trend)
            <div class="flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $trendColorClass }}">
                @if($trendDirection === 'up')
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                @elseif($trendDirection === 'down')
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                @endif
                <span>{{ $trend }}</span>
            </div>
            @endif
        </div>

        {{-- Title --}}
        <p class="text-sm text-gray-500 mb-1 truncate">{{ $title }}</p>

        {{-- Value --}}
        <p class="text-xl font-bold text-gray-900 truncate"
           @if($animate) x-text="displayValue" @endif
        >
            @if(!$animate){{ $value }}@endif
        </p>

        {{-- Optional slot for additional content --}}
        @if(isset($slot) && !$slot->isEmpty())
        <div class="mt-2 pt-2 border-t border-gray-100">
            {{ $slot }}
        </div>
        @endif
    </div>
    @endif
</div>

<style>
@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Skeleton shimmer effect - using existing project convention */
.skeleton {
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>
