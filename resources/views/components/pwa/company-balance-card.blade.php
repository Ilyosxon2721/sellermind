{{--
    Company Balance Card
    Большая карточка с общим балансом компании
--}}

@props([
    'period' => 'week',
])

<div
    x-data="{ showPeriodMenu: false }"
    class="rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-5 shadow-lg shadow-blue-500/20"
>
    {{-- Header with period selector --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-white/80 text-sm font-medium">Общий баланс</span>
        </div>

        {{-- Period Selector --}}
        <button
            @click="showPeriodMenu = !showPeriodMenu"
            class="flex items-center space-x-1 px-3 py-1.5 rounded-full bg-white/20 text-white text-sm font-medium hover:bg-white/30 transition-colors"
        >
            <span x-text="periodLabel">7 дней</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Main Balance --}}
    <div class="mb-4">
        <p class="text-4xl font-bold text-white tracking-tight" x-text="formatMoney(totalRevenue)">
            0 сум
        </p>
        <p class="text-blue-200 text-sm mt-1">
            <span x-text="totalOrders">0</span> заказов за период
        </p>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 gap-3">
        {{-- Today --}}
        <div class="bg-white/10 rounded-xl p-3">
            <p class="text-blue-200 text-xs font-medium mb-1">Сегодня</p>
            <p class="text-white font-semibold" x-text="formatMoney(todayRevenue)">0 сум</p>
            <p class="text-blue-300 text-xs"><span x-text="todayOrders">0</span> заказов</p>
        </div>

        {{-- Warehouse --}}
        <div class="bg-white/10 rounded-xl p-3">
            <p class="text-blue-200 text-xs font-medium mb-1">На складе</p>
            <p class="text-white font-semibold" x-text="formatMoney(warehouseValue)">0 сум</p>
            <p class="text-blue-300 text-xs"><span x-text="warehouseItems">0</span> позиций</p>
        </div>
    </div>

    {{-- Period Menu Dropdown --}}
    <div
        x-show="showPeriodMenu"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="showPeriodMenu = false"
        class="absolute right-4 mt-2 w-36 bg-white rounded-xl shadow-lg py-1 z-10"
    >
        <button
            @click="setPeriod('today'); showPeriodMenu = false"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            :class="period === 'today' ? 'font-semibold text-blue-600' : ''"
        >
            Сегодня
        </button>
        <button
            @click="setPeriod('week'); showPeriodMenu = false"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            :class="period === 'week' ? 'font-semibold text-blue-600' : ''"
        >
            7 дней
        </button>
        <button
            @click="setPeriod('month'); showPeriodMenu = false"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            :class="period === 'month' ? 'font-semibold text-blue-600' : ''"
        >
            30 дней
        </button>
    </div>
</div>
