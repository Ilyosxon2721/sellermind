@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Корзина</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-8">Корзина</h1>

    <div x-data="cartPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <svg class="w-8 h-8 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Пустая корзина --}}
        <div x-show="!loading && items.length === 0" class="text-center py-20">
            <svg class="w-24 h-24 mx-auto text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Корзина пуста</h2>
            <p class="text-gray-500 mb-8">Добавьте товары из каталога</p>
            <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-8 py-3 rounded-xl text-sm font-semibold inline-block">
                Перейти в каталог
            </a>
        </div>

        {{-- Содержимое корзины --}}
        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Список товаров --}}
            <div class="lg:col-span-2 space-y-4">
                <template x-for="item in items" :key="item.id">
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm flex gap-4">
                        {{-- Изображение --}}
                        <a :href="`/store/{{ $store->slug }}/product/${item.store_product_id}`" class="shrink-0">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl bg-gray-100 overflow-hidden">
                                <img
                                    x-show="item.image"
                                    :src="item.image"
                                    :alt="item.name"
                                    class="w-full h-full object-cover"
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
                                <a :href="`/store/{{ $store->slug }}/product/${item.store_product_id}`" class="text-sm sm:text-base font-medium text-gray-900 hover:text-gray-600 transition-colors line-clamp-2" x-text="item.name"></a>
                                <button
                                    @click="removeItem(item.id)"
                                    class="shrink-0 p-1.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    title="Удалить"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-1 text-sm font-semibold" style="color: var(--primary);" x-text="formatPrice(item.price) + ' {{ $currency }}'"></div>

                            <div class="mt-3 flex items-center justify-between">
                                {{-- Количество --}}
                                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                    <button
                                        @click="updateQuantity(item.id, item.quantity - 1)"
                                        :disabled="item.quantity <= 1"
                                        class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors disabled:opacity-30"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <span class="w-10 h-9 flex items-center justify-center text-sm font-medium border-x border-gray-200" x-text="item.quantity"></span>
                                    <button
                                        @click="updateQuantity(item.id, item.quantity + 1)"
                                        class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Сумма позиции --}}
                                <span class="text-sm font-bold text-gray-900" x-text="formatPrice(item.price * item.quantity) + ' {{ $currency }}'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Итого --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-28 space-y-5">
                    <h3 class="font-semibold text-lg">Итого</h3>

                    {{-- Промокод --}}
                    <div>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="promocode"
                                placeholder="Промокод"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                                @keydown.enter.prevent="applyPromocode()"
                            >
                            <button
                                @click="applyPromocode()"
                                :disabled="!promocode || promoLoading"
                                class="btn-primary px-4 py-2.5 rounded-xl text-sm font-medium disabled:opacity-50"
                            >
                                <span x-show="!promoLoading">OK</span>
                                <svg x-show="promoLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                        <p x-show="promoError" x-text="promoError" class="mt-1.5 text-xs text-red-500"></p>
                        <div x-show="promoApplied" class="mt-2 flex items-center gap-2 text-sm text-green-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Промокод применен</span>
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
                            <span class="font-medium" x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                        </div>
                        <div x-show="discount > 0" class="flex items-center justify-between text-sm">
                            <span class="text-green-600">Скидка</span>
                            <span class="font-medium text-green-600" x-text="'-' + formatPrice(discount) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-semibold">Итого</span>
                            <span class="text-xl font-bold" style="color: var(--primary);" x-text="formatPrice(total) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <a
                        href="/store/{{ $store->slug }}/checkout"
                        class="block w-full btn-primary py-3.5 rounded-xl text-center text-base font-semibold transition-all duration-200 hover:shadow-lg"
                    >
                        Оформить заказ
                    </a>

                    <a
                        href="/store/{{ $store->slug }}/catalog"
                        class="block text-center text-sm font-medium hover:opacity-75 transition-opacity"
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
                        const data = await response.json();
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
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ item_id: itemId, quantity }),
                    });
                    if (response.ok) {
                        const data = await response.json();
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
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ item_id: itemId }),
                    });
                    if (response.ok) {
                        const data = await response.json();
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
                    const data = await response.json();
                    if (response.ok) {
                        this.promoApplied = true;
                        this.discount = data.discount || 0;
                        this.total = data.total || 0;
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Промокод применен', type: 'success' }
                        }));
                    } else {
                        this.promoError = data.message || 'Недействительный промокод';
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
