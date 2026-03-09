{{--
    Marketplace Balance Card
    Карточка с балансом маркетплейса в Flutter-стиле
--}}

@props([
    'marketplace' => 'wildberries',
    'name' => 'Маркетплейс',
    'accountId' => null,
])

@php
$colors = [
    'wildberries' => ['bg' => 'bg-gradient-to-br from-purple-600 to-purple-700', 'text' => 'text-purple-100', 'icon' => 'WB'],
    'ozon' => ['bg' => 'bg-gradient-to-br from-blue-600 to-blue-700', 'text' => 'text-blue-100', 'icon' => 'OZ'],
    'uzum' => ['bg' => 'bg-gradient-to-br from-green-600 to-green-700', 'text' => 'text-green-100', 'icon' => 'UZ'],
    'yandex_market' => ['bg' => 'bg-gradient-to-br from-yellow-500 to-yellow-600', 'text' => 'text-yellow-100', 'icon' => 'YM'],
];
$config = $colors[$marketplace] ?? $colors['wildberries'];
@endphp

<a
    href="{{ $accountId ? '/marketplace/' . $accountId : '/marketplace' }}"
    class="block rounded-2xl {{ $config['bg'] }} p-4 shadow-sm hover:shadow-md transition-shadow active:scale-[0.98]"
    onclick="if(window.haptic) window.haptic.light()"
>
    <div class="flex items-center justify-between">
        {{-- Left: Icon + Info --}}
        <div class="flex items-center space-x-3">
            {{-- Marketplace Icon --}}
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                <span class="text-white font-bold text-lg">{{ $config['icon'] }}</span>
            </div>

            {{-- Info --}}
            <div class="min-w-0">
                <p class="text-sm {{ $config['text'] }} truncate">{{ $name }}</p>
                <p class="text-xl font-bold text-white truncate" x-text="formatMoney(revenue)">0 сум</p>
                <p class="text-xs {{ $config['text'] }}">
                    <span x-text="ordersCount">0</span> заказов
                </p>
            </div>
        </div>

        {{-- Right: Arrow --}}
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </div>

    {{-- Bottom Stats Row --}}
    <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between">
        <div class="flex items-center space-x-1 text-xs {{ $config['text'] }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="lastSync || 'Не синхронизировано'">--</span>
        </div>

        <div class="flex items-center space-x-1">
            <span class="w-2 h-2 rounded-full" :class="isActive ? 'bg-green-400' : 'bg-gray-400'"></span>
            <span class="text-xs {{ $config['text'] }}" x-text="isActive ? 'Активен' : 'Неактивен'"></span>
        </div>
    </div>
</a>
