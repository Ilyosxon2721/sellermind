{{-- Фильтры каталога (для sidebar и mobile drawer) --}}
<form action="/store/{{ $slug }}/catalog" method="GET">
    @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
    @endif
    @if(request('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
    @endif
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    {{-- Цена --}}
    <div class="space-y-3">
        <h4 class="text-sm font-semibold text-gray-900">Цена</h4>
        <div class="flex items-center gap-2">
            <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="от"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: var(--primary);">
            <span class="text-gray-400 text-sm">—</span>
            <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="до"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: var(--primary);">
        </div>
        <button type="submit" class="w-full py-2 text-sm font-medium rounded-lg text-white transition-colors hover:opacity-90" style="background: var(--primary);">
            Применить
        </button>
    </div>
</form>

{{-- Активные фильтры --}}
@if(request('price_min') || request('price_max'))
    <div class="pt-3 border-t border-gray-100">
        <a href="/store/{{ $slug }}/catalog{{ request('category') ? '?category=' . request('category') : '' }}{{ request('sort') ? (request('category') ? '&' : '?') . 'sort=' . request('sort') : '' }}"
           class="text-sm text-red-500 hover:text-red-600 font-medium">
            Сбросить фильтры
        </a>
    </div>
@endif
