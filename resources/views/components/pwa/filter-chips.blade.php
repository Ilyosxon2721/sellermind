@props([
    'chips' => [],
    'model' => 'marketplace',
    'multiple' => false,
])

@php
// Default chips if none provided
$defaultChips = [
    ['value' => 'all', 'label' => 'Все'],
    ['value' => 'wb', 'label' => 'WB', 'color' => '#CB11AB'],
    ['value' => 'ozon', 'label' => 'Ozon', 'color' => '#005BFF'],
    ['value' => 'uzum', 'label' => 'Uzum', 'color' => '#7000FF'],
    ['value' => 'yandex', 'label' => 'Yandex', 'color' => '#FFCC00'],
    ['value' => 'in_stock', 'label' => 'В наличии', 'icon' => 'check'],
    ['value' => 'out_of_stock', 'label' => 'Нет в наличии', 'icon' => 'x'],
];

$chips = empty($chips) ? $defaultChips : $chips;
@endphp

{{--
    PWA Filter Chips Component
    - Horizontal scroll with snap
    - Haptic feedback on tap
    - Active state: bg-blue-600
    - Supports single/multiple selection
--}}

<div
    x-data="{
        selected: @if($multiple) [] @else 'all' @endif,

        init() {
            @if($model)
                this.selected = $store.productFilters?.{{ $model }} ?? @if($multiple) [] @else 'all' @endif;
            @endif
        },

        isActive(value) {
            @if($multiple)
                return this.selected.includes(value);
            @else
                return this.selected === value;
            @endif
        },

        toggle(value) {
            if (navigator.vibrate) navigator.vibrate(10);

            @if($multiple)
                if (value === 'all') {
                    this.selected = [];
                } else {
                    const index = this.selected.indexOf(value);
                    if (index > -1) {
                        this.selected.splice(index, 1);
                    } else {
                        this.selected.push(value);
                    }
                }
            @else
                this.selected = value;
            @endif

            @if($model)
                if ($store.productFilters) {
                    $store.productFilters.{{ $model }} = this.selected;
                }
            @endif

            $dispatch('filter-change', {
                filter: '{{ $model }}',
                value: this.selected
            });
        }
    }"
    {{ $attributes->merge(['class' => 'pwa-only']) }}
>
    <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide snap-x snap-mandatory scroll-smooth">
        @foreach($chips as $chip)
            @php
                $chipValue = is_array($chip) ? $chip['value'] : $chip;
                $chipLabel = is_array($chip) ? $chip['label'] : $chip;
                $chipColor = is_array($chip) && isset($chip['color']) ? $chip['color'] : null;
                $chipIcon = is_array($chip) && isset($chip['icon']) ? $chip['icon'] : null;
            @endphp

            <button
                type="button"
                @click="toggle('{{ $chipValue }}')"
                :class="isActive('{{ $chipValue }}')
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-gray-100 text-gray-700 border-gray-200'"
                class="flex-none snap-start flex items-center gap-1.5 h-9 px-4 rounded-full text-sm font-medium border transition-all duration-150 active:scale-95 whitespace-nowrap"
            >
                {{-- Marketplace color dot --}}
                @if($chipColor && $chipValue !== 'all')
                    <span
                        class="w-2.5 h-2.5 rounded-full flex-none"
                        :class="isActive('{{ $chipValue }}') ? 'opacity-90' : ''"
                        style="background-color: {{ $chipColor }}"
                    ></span>
                @endif

                {{-- Status icon --}}
                @if($chipIcon === 'check')
                    <svg class="w-4 h-4 flex-none" :class="isActive('{{ $chipValue }}') ? 'text-white' : 'text-green-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                @elseif($chipIcon === 'x')
                    <svg class="w-4 h-4 flex-none" :class="isActive('{{ $chipValue }}') ? 'text-white' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                @endif

                <span>{{ $chipLabel }}</span>

                {{-- Active check for multiple mode --}}
                @if($multiple)
                    <svg
                        x-show="isActive('{{ $chipValue }}')"
                        x-cloak
                        class="w-4 h-4 flex-none ml-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                @endif
            </button>
        @endforeach
    </div>
</div>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
