{{-- Storefront Header --}}
@php
    $theme = $store->theme;
    $headerStyle = $theme->header_style ?? 'default';
    $showSearch = $theme->show_search ?? true;
    $showCart = $theme->show_cart ?? true;
    $showPhone = $theme->show_phone ?? true;
    $menuCategories = $store->visibleCategories()->where('show_in_menu', true)->get();
    $menuPages = $store->activePages()->where('show_in_menu', true)->get();
@endphp

<header
    x-data="storeHeader()"
    x-cloak
    class="sticky top-0 z-40 shadow-sm transition-shadow duration-300"
    style="background: var(--header-bg); color: var(--header-text);"
>
    {{-- Верхняя полоса с телефоном (если включен) --}}
    @if($showPhone && $store->phone)
        <div class="bg-gray-900 text-white text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="tel:{{ $store->phone }}" class="flex items-center gap-1.5 hover:text-gray-300 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $store->phone }}
                    </a>
                    @if($store->email)
                        <a href="mailto:{{ $store->email }}" class="hidden sm:flex items-center gap-1.5 hover:text-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $store->email }}
                        </a>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if($store->instagram)
                        <a href="https://instagram.com/{{ $store->instagram }}" target="_blank" rel="noopener" class="hover:text-gray-300 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    @endif
                    @if($store->telegram)
                        <a href="https://t.me/{{ $store->telegram }}" target="_blank" rel="noopener" class="hover:text-gray-300 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Основной хедер --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($headerStyle === 'centered')
            {{-- Центрированный стиль --}}
            <div class="flex flex-col items-center py-4">
                {{-- Логотип --}}
                <a href="/store/{{ $store->slug }}" class="mb-3">
                    @if($store->logo)
                        <img src="{{ Str::startsWith($store->logo, 'http') ? $store->logo : asset('storage/' . $store->logo) }}" alt="{{ $store->name }}" class="h-12 object-contain">
                    @else
                        <span class="text-2xl font-bold" style="color: var(--header-text);">{{ $store->name }}</span>
                    @endif
                </a>

                {{-- Навигация (desktop) --}}
                <nav class="hidden lg:flex items-center gap-6">
                    <a href="/store/{{ $store->slug }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                        Главная
                    </a>
                    <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium hover:opacity-75 transition-opacity">
                        Каталог
                    </a>
                    @foreach($menuCategories as $cat)
                        <a href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                            {{ $cat->custom_name ?: $cat->category->name }}
                        </a>
                    @endforeach
                    @foreach($menuPages as $page)
                        <a href="/store/{{ $store->slug }}/page/{{ $page->slug }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                            {{ $page->title }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Иконки: поиск, корзина (по центру, ниже навигации на десктопе) --}}
            <div class="hidden lg:flex items-center justify-center gap-4 pb-3 -mt-1">
                @if($showSearch)
                    <button @click="toggleSearch()" class="p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Поиск">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                @endif
                @if($showCart)
                    <a href="/store/{{ $store->slug }}/cart" class="relative p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Корзина">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span
                            x-show="cartCount > 0"
                            x-text="cartCount"
                            class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-white text-xs flex items-center justify-center font-bold bg-theme-accent"
                            style="background: var(--accent);"
                        ></span>
                    </a>
                @endif
            </div>
        @elseif($headerStyle === 'minimal')
            {{-- Минимальный стиль --}}
            <div class="flex items-center justify-between py-3">
                <a href="/store/{{ $store->slug }}">
                    @if($store->logo)
                        <img src="{{ Str::startsWith($store->logo, 'http') ? $store->logo : asset('storage/' . $store->logo) }}" alt="{{ $store->name }}" class="h-8 object-contain">
                    @else
                        <span class="text-lg font-bold" style="color: var(--header-text);">{{ $store->name }}</span>
                    @endif
                </a>

                <div class="flex items-center gap-2">
                    @if($showSearch)
                        <button @click="toggleSearch()" class="p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Поиск">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    @endif
                    @if($showCart)
                        <a href="/store/{{ $store->slug }}/cart" class="relative p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Корзина">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span
                                x-show="cartCount > 0"
                                x-text="cartCount"
                                class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-white text-xs flex items-center justify-center font-bold"
                                style="background: var(--accent);"
                            ></span>
                        </a>
                    @endif
                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Меню">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            {{-- Стандартный стиль (default) --}}
            <div class="flex items-center justify-between py-4">
                {{-- Логотип --}}
                <a href="/store/{{ $store->slug }}" class="shrink-0">
                    @if($store->logo)
                        <img src="{{ Str::startsWith($store->logo, 'http') ? $store->logo : asset('storage/' . $store->logo) }}" alt="{{ $store->name }}" class="h-10 object-contain">
                    @else
                        <span class="text-xl font-bold" style="color: var(--header-text);">{{ $store->name }}</span>
                    @endif
                </a>

                {{-- Навигация (desktop) --}}
                <nav class="hidden lg:flex items-center gap-6 mx-8">
                    <a href="/store/{{ $store->slug }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                        Главная
                    </a>
                    <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium hover:opacity-75 transition-opacity">
                        Каталог
                    </a>
                    @foreach($menuCategories as $cat)
                        <a href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                            {{ $cat->custom_name ?: $cat->category->name }}
                        </a>
                    @endforeach
                    @foreach($menuPages as $page)
                        <a href="/store/{{ $store->slug }}/page/{{ $page->slug }}" class="text-sm font-medium hover:opacity-75 transition-opacity">
                            {{ $page->title }}
                        </a>
                    @endforeach
                </nav>

                {{-- Иконки --}}
                <div class="flex items-center gap-2">
                    @if($showSearch)
                        <button @click="toggleSearch()" class="p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Поиск">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    @endif
                    @if($showCart)
                        <a href="/store/{{ $store->slug }}/cart" class="relative p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Корзина">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span
                                x-show="cartCount > 0"
                                x-text="cartCount"
                                class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-white text-xs flex items-center justify-center font-bold"
                                style="background: var(--accent);"
                            ></span>
                        </a>
                    @endif

                    {{-- Мобильное меню (гамбургер) --}}
                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-full hover:bg-black/5 transition-colors" aria-label="Меню">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Поиск (выпадающий) --}}
    <div
        x-show="searchOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-cloak
        class="border-t border-gray-100"
        style="background: var(--header-bg);"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <form action="/store/{{ $store->slug }}/catalog" method="GET" class="flex items-center gap-3">
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="search"
                        name="search"
                        x-ref="searchInput"
                        placeholder="Поиск товаров..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent text-sm"
                        style="--tw-ring-color: var(--primary);"
                    >
                </div>
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium">
                    Найти
                </button>
                <button type="button" @click="searchOpen = false" class="p-2 rounded-full hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Мобильное меню --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.outside="mobileOpen = false"
        x-cloak
        class="lg:hidden border-t border-gray-100"
        style="background: var(--header-bg);"
    >
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            <a href="/store/{{ $store->slug }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                Главная
            </a>
            <a href="/store/{{ $store->slug }}/catalog" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                Каталог
            </a>
            @foreach($menuCategories as $cat)
                <a href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                    {{ $cat->custom_name ?: $cat->category->name }}
                </a>
            @endforeach
            @foreach($menuPages as $page)
                <a href="/store/{{ $store->slug }}/page/{{ $page->slug }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                    {{ $page->title }}
                </a>
            @endforeach

            @if($showCart)
                <div class="pt-2 border-t border-gray-100 mt-2">
                    <a href="/store/{{ $store->slug }}/cart" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Корзина
                        <span
                            x-show="cartCount > 0"
                            x-text="'(' + cartCount + ')'"
                            class="text-theme-primary font-bold"
                            style="color: var(--primary);"
                        ></span>
                    </a>
                </div>
            @endif

            @if($store->phone)
                <div class="pt-2 border-t border-gray-100 mt-2">
                    <a href="tel:{{ $store->phone }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-black/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $store->phone }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</header>

<script>
    function storeHeader() {
        return {
            mobileOpen: false,
            searchOpen: false,
            cartCount: 0,

            init() {
                this.fetchCartCount();

                window.addEventListener('cart-updated', () => {
                    this.fetchCartCount();
                });
            },

            toggleSearch() {
                this.searchOpen = !this.searchOpen;
                if (this.searchOpen) {
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                }
            },

            async fetchCartCount() {
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.cartCount = data.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
                    }
                } catch (e) {
                    // Корзина может быть недоступна - игнорируем
                }
            }
        }
    }
</script>
