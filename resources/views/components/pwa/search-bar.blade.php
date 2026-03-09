@props([
    'placeholder' => 'Поиск товаров...',
    'model' => null,
    'debounce' => 300,
])

{{--
    PWA Search Bar Component
    - Touch-friendly 44px height
    - Debounce 300ms by default
    - Clear button when text exists
    - Focus ring animation
--}}

<div
    x-data="{
        query: '',
        debounceTimer: null,

        init() {
            @if($model)
                this.query = $store.productFilters?.{{ $model }} ?? '';
                this.$watch('query', (value) => this.handleInput(value));
            @endif
        },

        handleInput(value) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                @if($model)
                    if ($store.productFilters) {
                        $store.productFilters.{{ $model }} = value;
                    }
                @endif
                $dispatch('search', { query: value });
            }, {{ $debounce }});
        },

        clear() {
            this.query = '';
            @if($model)
                if ($store.productFilters) {
                    $store.productFilters.{{ $model }} = '';
                }
            @endif
            $dispatch('search', { query: '' });
            this.$refs.input.focus();
            if (navigator.vibrate) navigator.vibrate(10);
        }
    }"
    {{ $attributes->merge(['class' => 'pwa-only relative']) }}
>
    {{-- Search Icon --}}
    <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    </div>

    {{-- Input Field --}}
    <input
        x-ref="input"
        x-model="query"
        type="text"
        inputmode="search"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        autocorrect="off"
        autocapitalize="off"
        spellcheck="false"
        class="w-full h-11 pl-11 pr-10 bg-gray-100 rounded-full text-base text-gray-900 placeholder-gray-500 outline-none transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 focus:bg-white"
    />

    {{-- Clear Button --}}
    <button
        x-show="query.length > 0"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-75"
        @click="clear()"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center w-6 h-6 bg-gray-400 rounded-full active:scale-90 transition-transform"
    >
        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>
</div>
