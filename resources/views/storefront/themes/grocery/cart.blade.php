@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    {{-- Хлебные крошки --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-2">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity flex items-center gap-1" style="color: var(--primary);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Главная
        </a>
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 font-medium">Корзина</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-8 flex items-center gap-3">
        <svg class="w-8 h-8" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        Корзина
    </h1>

    <div x-data="groceryCartPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="text-center">
                <svg class="w-10 h-10 animate-spin mx-auto mb-3" style="color: var(--primary);" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-gray-500 text-sm">Загружаем корзину...</p>
            </div>
        </div>

        {{-- Пустая корзина --}}
        <div x-show="!loading && items.length === 0" class="text-center py-20">
            <div class="w-28 h-28 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-6">
                <svg class="w-14 h-14 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Корзина пуста</h2>
            <p class="text-gray-500 mb-8">Добавьте свежие товары из нашего каталога</p>
            <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-10 py-3.5 rounded-full text-base font-bold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                Перейти в каталог
            </a>
        </div>

        {{-- Содержимое корзины --}}
        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Список товаров --}}
            <div class="lg:col-span-2 space-y-4">
                <template x-for="item in items" :key="item.product_id">
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 flex gap-4 hover:shadow-md transition-shadow">
                        {{-- Изображение --}}
                        <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="shrink-0">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gray-50 overflow-hidden border border-gray-100 p-2">
                                <img
                                    x-show="item.image"
                                    :src="item.image"
                                    :alt="item.name"
                                    class="w-full h-full object-contain"
                                >
                                <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>

                        {{-- Информация --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="text-sm sm:text-base font-semibold text-gray-900 hover:text-gray-600 transition-colors line-clamp-2" x-text="item.name"></a>
                                <button
                                    @click="removeItem(item.product_id)"
                                    class="shrink-0 p-2 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    title="Удалить"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-1 text-base font-bold" style="color: var(--primary);" x-text="formatPrice(item.price) + ' {{ $currency }}'"></div>

                            <div class="mt-3 flex items-center justify-between">
                                {{-- Количество --}}
                                <div class="flex items-center bg-gray-100 rounded-full overflow-hidden">
                                    <button
                                        @click="updateQuantity(item.product_id, item.quantity - 1)"
                                        :disabled="item.quantity <= 1"
                                        class="w-10 h-10 flex items-center justify-center hover:bg-gray-200 transition-colors disabled:opacity-30 rounded-full"
                                        style="color: var(--primary);"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <span class="w-10 h-10 flex items-center justify-center text-sm font-bold" x-text="item.quantity"></span>
                                    <button
                                        @click="updateQuantity(item.product_id, item.quantity + 1)"
                                        class="w-10 h-10 flex items-center justify-center hover:bg-gray-200 transition-colors rounded-full"
                                        style="color: var(--primary);"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Сумма позиции --}}
                                <span class="text-base font-bold text-gray-900" x-text="formatPrice(item.price * item.quantity) + ' {{ $currency }}'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Итого --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 sticky top-28 space-y-5">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Итого
                    </h3>

                    {{-- Промокод --}}
                    <div>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="promocode"
                                placeholder="Промокод"
                                class="flex-1 px-4 py-3 rounded-full border-2 border-gray-200 text-sm font-medium focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                                @keydown.enter.prevent="applyPromocode()"
                            >
                            <button
                                @click="applyPromocode()"
                                :disabled="!promocode || promoLoading"
                                class="btn-primary px-5 py-3 rounded-full text-sm font-bold disabled:opacity-50"
                            >
                                <span x-show="!promoLoading">OK</span>
                                <svg x-show="promoLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                        <p x-show="promoError" x-text="promoError" class="mt-1.5 text-xs text-red-500 font-medium"></p>
                        <div x-show="promoApplied" class="mt-2 flex items-center gap-2 text-sm text-green-600 bg-green-50 rounded-full px-4 py-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="font-medium">Промокод применен</span>
                            <button @click="removePromocode()" class="ml-auto text-gray-400 hover:text-red-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Суммы --}}
                    <div class="space-y-3 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Подитог</span>
                            <span class="font-semibold" x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                        </div>
                        <div x-show="discount > 0" class="flex items-center justify-between text-sm">
                            <span class="text-green-600 font-medium">Скидка</span>
                            <span class="font-semibold text-green-600" x-text="'-' + formatPrice(discount) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <div class="border-t-2 border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold">Итого</span>
                            <span class="text-2xl font-bold" style="color: var(--primary);" x-text="formatPrice(total) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <a
                        href="/store/{{ $store->slug }}/checkout"
                        class="block w-full btn-primary py-4 rounded-full text-center text-lg font-bold transition-all duration-200 hover:shadow-xl hover:scale-[1.02]"
                    >
                        Оформить заказ
                    </a>

                    <a
                        href="/store/{{ $store->slug }}/catalog"
                        class="block text-center text-sm font-semibold hover:opacity-75 transition-opacity"
                        style="color: var(--primary);"
                    >
                        Продолжить покупки
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function groceryCartPage() {
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
