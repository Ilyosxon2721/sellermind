@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-xs font-mono text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-700">Корзина</span>
    </nav>

    <div class="flex items-center gap-3 mb-6">
        <div class="w-1 h-6 rounded-sm" style="background: var(--primary);"></div>
        <h1 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">Корзина</h1>
    </div>

    <div x-data="cartPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-16">
            <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Пустая корзина --}}
        <div x-show="!loading && items.length === 0" class="text-center py-16 border border-dashed border-gray-300 rounded-lg">
            <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 mb-1">Корзина пуста</h2>
            <p class="text-xs text-gray-400 font-mono mb-5">Добавьте товары из каталога</p>
            <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-6 py-2.5 rounded-lg text-xs font-semibold uppercase tracking-wider inline-block">
                Перейти в каталог
            </a>
        </div>

        {{-- Содержимое корзины --}}
        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Таблица товаров --}}
            <div class="lg:col-span-2">
                {{-- Desktop таблица --}}
                <div class="hidden sm:block border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-900 text-white text-xs font-mono uppercase tracking-wider">
                                <th class="text-left px-4 py-2.5">Товар</th>
                                <th class="text-center px-3 py-2.5 w-24">Цена</th>
                                <th class="text-center px-3 py-2.5 w-28">Кол-во</th>
                                <th class="text-right px-3 py-2.5 w-28">Сумма</th>
                                <th class="w-10 px-2 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="item.product_id">
                                <tr class="border-t border-gray-100" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="shrink-0">
                                                <div class="w-12 h-12 rounded border border-gray-200 bg-gray-100 overflow-hidden">
                                                    <img
                                                        x-show="item.image"
                                                        :src="item.image"
                                                        :alt="item.name"
                                                        class="w-full h-full object-contain p-0.5"
                                                    >
                                                    <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </a>
                                            <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="text-sm font-medium text-gray-900 hover:text-gray-600 transition-colors line-clamp-2" x-text="item.name"></a>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono text-sm" x-text="formatPrice(item.price)"></td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center justify-center border border-gray-200 rounded overflow-hidden mx-auto w-fit">
                                            <button
                                                @click="updateQuantity(item.product_id, item.quantity - 1)"
                                                :disabled="item.quantity <= 1"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors disabled:opacity-30"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                            </button>
                                            <span class="w-8 h-7 flex items-center justify-center text-xs font-mono font-semibold border-x border-gray-200" x-text="item.quantity"></span>
                                            <button
                                                @click="updateQuantity(item.product_id, item.quantity + 1)"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono font-bold text-sm" x-text="formatPrice(item.price * item.quantity)"></td>
                                    <td class="px-2 py-3 text-center">
                                        <button
                                            @click="removeItem(item.product_id)"
                                            class="p-1 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                                            title="Удалить"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Mobile карточки --}}
                <div class="sm:hidden space-y-2">
                    <template x-for="item in items" :key="item.product_id">
                        <div class="bg-white border border-gray-200 rounded-lg p-3 flex gap-3">
                            <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="shrink-0">
                                <div class="w-16 h-16 rounded border border-gray-200 bg-gray-100 overflow-hidden">
                                    <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-contain p-0.5">
                                    <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="text-sm font-medium text-gray-900 line-clamp-1" x-text="item.name"></a>
                                    <button @click="removeItem(item.product_id)" class="shrink-0 p-0.5 text-gray-300 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-0.5 text-xs font-mono" style="color: var(--primary);" x-text="formatPrice(item.price) + ' {{ $currency }}'"></div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center border border-gray-200 rounded overflow-hidden">
                                        <button @click="updateQuantity(item.product_id, item.quantity - 1)" :disabled="item.quantity <= 1" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-50 disabled:opacity-30">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="w-8 h-7 flex items-center justify-center text-xs font-mono font-semibold border-x border-gray-200" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.product_id, item.quantity + 1)" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                    <span class="text-sm font-bold font-mono" x-text="formatPrice(item.price * item.quantity) + ' {{ $currency }}'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Итого --}}
            <div class="lg:col-span-1">
                <div class="border border-gray-200 rounded-lg sticky top-28">
                    <div class="bg-gray-900 text-white px-4 py-3">
                        <h3 class="text-sm font-bold uppercase tracking-wider">Итого</h3>
                    </div>

                    <div class="p-4 space-y-4">
                        {{-- Промокод --}}
                        <div>
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    x-model="promocode"
                                    placeholder="Промокод"
                                    class="flex-1 px-3 py-2 rounded border border-gray-200 text-xs font-mono focus:outline-none focus:ring-1 focus:border-transparent"
                                    style="--tw-ring-color: var(--primary);"
                                    @keydown.enter.prevent="applyPromocode()"
                                >
                                <button
                                    @click="applyPromocode()"
                                    :disabled="!promocode || promoLoading"
                                    class="btn-primary px-3 py-2 rounded text-xs font-bold disabled:opacity-50"
                                >
                                    <span x-show="!promoLoading">OK</span>
                                    <svg x-show="promoLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="promoError" x-text="promoError" class="mt-1 text-xs text-red-500 font-mono"></p>
                            <div x-show="promoApplied" class="mt-1.5 flex items-center gap-1.5 text-xs text-green-600 font-mono">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Промокод применен</span>
                                <button @click="removePromocode()" class="ml-auto text-gray-400 hover:text-red-500">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Суммы --}}
                        <div class="space-y-2 border-t border-gray-100 pt-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 font-mono uppercase">Подитог</span>
                                <span class="font-mono font-semibold" x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                            </div>
                            <div x-show="discount > 0" class="flex items-center justify-between text-xs">
                                <span class="text-green-600 font-mono uppercase">Скидка</span>
                                <span class="font-mono font-semibold text-green-600" x-text="'-' + formatPrice(discount) + ' {{ $currency }}'"></span>
                            </div>
                        </div>

                        <div class="border-l-4 pl-3 py-2" style="border-color: var(--primary);">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider">Итого</span>
                                <span class="text-lg font-bold font-mono" style="color: var(--primary);" x-text="formatPrice(total) + ' {{ $currency }}'"></span>
                            </div>
                        </div>

                        <a
                            href="/store/{{ $store->slug }}/checkout"
                            class="block w-full btn-primary py-3 rounded-lg text-center text-sm font-bold uppercase tracking-wider transition-all duration-200 hover:shadow-lg"
                        >
                            Оформить заказ
                        </a>

                        <a
                            href="/store/{{ $store->slug }}/catalog"
                            class="block text-center text-xs font-mono uppercase tracking-wider hover:opacity-75 transition-opacity"
                            style="color: var(--primary);"
                        >
                            Продолжить покупки
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cartPage() {
        return {
            loading: true,
            items: [],
            subtotal: 0,
            discount: 0,
            total: 0,
            promocode: '',
            promoApplied: false,
            promoLoading: false,
            promoError: '',

            async init() {
                await this.fetchCart();
            },

            async fetchCart() {
                this.loading = true;
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    if (response.ok) {
                        const json = await response.json();
                        const data = json.data || json;
                        this.items = data.items || [];
                        this.subtotal = data.subtotal || 0;
                        this.discount = data.discount || 0;
                        this.total = data.total || 0;
                        if (data.promocode) {
                            this.promocode = data.promocode;
                            this.promoApplied = true;
                        }
                    }
                } catch (e) {
                    // Ignore
                } finally {
                    this.loading = false;
                }
            },

            async updateQuantity(itemId, quantity) {
                if (quantity < 1) return;
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart/update`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ product_id: itemId, quantity }),
                    });
                    if (response.ok) {
                        const json = await response.json();
                        const data = json.data || json;
                        this.items = data.items || [];
                        this.subtotal = data.subtotal || 0;
                        this.discount = data.discount || 0;
                        this.total = data.total || 0;
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Ошибка обновления', type: 'error' }
                    }));
                }
            },

            async removeItem(itemId) {
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart/remove`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ product_id: itemId }),
                    });
                    if (response.ok) {
                        const json = await response.json();
                        const data = json.data || json;
                        this.items = data.items || [];
                        this.subtotal = data.subtotal || 0;
                        this.discount = data.discount || 0;
                        this.total = data.total || 0;
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Товар удален из корзины', type: 'success' }
                        }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Ошибка удаления', type: 'error' }
                    }));
                }
            },

            async applyPromocode() {
                if (!this.promocode) return;
                this.promoLoading = true;
                this.promoError = '';
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart/promocode`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ code: this.promocode }),
                    });
                    const json = await response.json();
                    if (response.ok) {
                        const data = json.data || json;
                        this.promoApplied = true;
                        this.discount = data.discount || 0;
                        this.total = data.total || 0;
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Промокод применен', type: 'success' }
                        }));
                    } else {
                        this.promoError = json.errors?.[0]?.message || json.message || 'Недействительный промокод';
                    }
                } catch (e) {
                    this.promoError = 'Ошибка соединения';
                } finally {
                    this.promoLoading = false;
                }
            },

            async removePromocode() {
                try {
                    const slug = '{{ $store->slug }}';
                    await fetch(`/store/${slug}/api/cart/promocode`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    this.promoApplied = false;
                    this.promocode = '';
                    this.discount = 0;
                    await this.fetchCart();
                } catch (e) {
                    // Ignore
                }
            },
        }
    }
</script>
@endsection
