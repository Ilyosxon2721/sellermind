{{-- Storefront Footer --}}
@php
    $theme = $store->theme;
    $footerPages = $store->activePages()->where('show_in_footer', true)->get();
@endphp

<footer
    class="mt-auto"
    style="background: var(--footer-bg); color: var(--footer-text);"
>
    {{-- Основной контент --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            {{-- О магазине --}}
            <div>
                <div class="mb-4">
                    @if($store->logo)
                        <img src="{{ Str::startsWith($store->logo, 'http') ? $store->logo : asset('storage/' . $store->logo) }}" alt="{{ $store->name }}" class="h-8 object-contain brightness-0 invert opacity-90">
                    @else
                        <h3 class="text-lg font-bold">{{ $store->name }}</h3>
                    @endif
                </div>
                @if($store->description)
                    <p class="text-sm opacity-70 leading-relaxed">{{ Str::limit($store->description, 200) }}</p>
                @endif

                {{-- Социальные сети --}}
                <div class="flex items-center gap-3 mt-5">
                    @if($store->instagram)
                        <a
                            href="https://instagram.com/{{ $store->instagram }}"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors"
                            aria-label="Instagram"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    @endif
                    @if($store->telegram)
                        <a
                            href="https://t.me/{{ $store->telegram }}"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors"
                            aria-label="Telegram"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                    @endif
                    @if($store->facebook)
                        <a
                            href="https://facebook.com/{{ $store->facebook }}"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors"
                            aria-label="Facebook"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Страницы --}}
            @if($footerPages->isNotEmpty())
                <div>
                    <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 opacity-90">Информация</h4>
                    <ul class="space-y-2.5">
                        @foreach($footerPages as $page)
                            <li>
                                <a href="/store/{{ $store->slug }}/page/{{ $page->slug }}" class="text-sm opacity-70 hover:opacity-100 transition-opacity">
                                    {{ $page->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Покупателям --}}
            <div>
                <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 opacity-90">Покупателям</h4>
                <ul class="space-y-2.5">
                    <li>
                        <a href="/store/{{ $store->slug }}/catalog" class="text-sm opacity-70 hover:opacity-100 transition-opacity">
                            Каталог
                        </a>
                    </li>
                    <li>
                        <a href="/store/{{ $store->slug }}/cart" class="text-sm opacity-70 hover:opacity-100 transition-opacity">
                            Корзина
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Контакты --}}
            <div>
                <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 opacity-90">Контакты</h4>
                <ul class="space-y-3">
                    @if($store->phone)
                        <li>
                            <a href="tel:{{ $store->phone }}" class="flex items-start gap-2.5 text-sm opacity-70 hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $store->phone }}
                            </a>
                        </li>
                    @endif
                    @if($store->email)
                        <li>
                            <a href="mailto:{{ $store->email }}" class="flex items-start gap-2.5 text-sm opacity-70 hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $store->email }}
                            </a>
                        </li>
                    @endif
                    @if($store->address)
                        <li class="flex items-start gap-2.5 text-sm opacity-70">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $store->address }}
                        </li>
                    @endif
                    @if($store->working_hours)
                        <li class="flex items-start gap-2.5 text-sm opacity-70">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>
                                @if(is_array($store->working_hours))
                                    @foreach($store->working_hours as $day => $hours)
                                        {{ $day }}: {{ $hours }}<br>
                                    @endforeach
                                @else
                                    {{ $store->working_hours }}
                                @endif
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Нижняя полоса --}}
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs opacity-50">
                    {{ $theme->footer_text ?? '&copy; ' . date('Y') . ' ' . $store->name . '. Все права защищены.' }}
                </p>
                <p class="text-xs opacity-40">
                    Создано на <a href="https://sellermind.uz" target="_blank" rel="noopener" class="hover:opacity-75 underline transition-opacity">SellerMind</a>
                </p>
            </div>
        </div>
    </div>
</footer>
