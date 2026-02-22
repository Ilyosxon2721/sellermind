@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    {{-- Хлебные крошки --}}
    <nav class="mb-8 text-sm text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:text-gray-900 transition-colors">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Корзина</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-semibold mb-10">Корзина</h1>

    <div x-data="minimalCartPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-24">
            <svg class="w-6 h-6 animate-spin text-gray-300" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Пустая корзина --}}
        <div x-show="!loading && items.length === 0" class="text-center py-24">
            <svg class="w-16 h-16 mx-auto text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h2 class="text-lg font-medium text-gray-900 mb-2">Корзина пуста</h2>
            <p class="text-gray-400 mb-8">Добавьте товары из каталога</p>
            <a href="/store/{{ $store->slug }}/catalog" class="inline-block px-8 py-2.5 border border-gray-900 text-gray-900 rounded text-sm font-medium hover:bg-gray-900 hover:text-white transition-colors">
                Перейти в каталог
            </a>
        </div>

        {{-- Содержимое корзины --}}
        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            {{-- Список товаров --}}
            <div class="lg:col-span-2">
                {{-- Заголовок таблицы (desktop) --}}
                <div class="hidden sm:grid grid-cols-12 gap-4 pb-4 border-b border-gray-200 text-xs font-medium uppercase tracking-widest text-gray-400">
                    <div class="col-span-6">Товар</div>
                    <div class="col-span-2 text-center">Кол-во</div>
                    <div class="col-span-3 text-right">Сумма</div>
                    <div class="col-span-1"></div>
                </div>

                <template x-for="item in items" :key="item.product_id">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 py-6 border-b border-gray-100 items-center">
                        {{-- Товар --}}
                        <div class="sm:col-span-6 flex items-center gap-4">
                            <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="shrink-0">
                                <div class="w-16 h-16 rounded border border-gray-200 bg-gray-50 overflow-hidden">
                                    <img
                                        x-show="item.image"
                                        :src="item.image"
                                        :alt="item.name"
                                        class="w-full h-full object-cover"
                                    >
                                    <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                            <div class="min-w-0">
                                <a :href="`/store/{{ $store->slug }}/product/${item.product_id}`" class="text-sm font-medium text-gray-900 hover:text-gray-600 transition-colors line-clamp-2" x-text="item.name"></a>
                                <p class="text-sm text-gray-400 mt-0.5" x-text="formatPrice(item.price) + ' {{ $currency }}'"></p>
                            </div>
                        </div>

                        {{-- Количество --}}
                        <div class="sm:col-span-2 flex justify-center">
                            <div class="flex items-center border border-gray-200 rounded overflow-hidden">
                                <button
                                    @click="updateQuantity(item.product_id, item.quantity - 1)"
                                    :disabled="item.quantity <= 1"
                                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-colors disabled:opacity-30"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <span class="w-8 h-8 flex items-center justify-center text-sm font-medium border-x border-gray-200" x-text="item.quantity"></span>
                                <button
                                    @click="updateQuantity(item.product_id, item.quantity + 1)"
                                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Сумма --}}
                        <div class="sm:col-span-3 text-right">
                            <span class="text-sm font-medium text-gray-900" x-text="formatPrice(item.price * item.quantity) + ' {{ $currency }}'"></span>
                        </div>

                        {{-- Удалить --}}
                        <div class="sm:col-span-1 text-right">
                            <button
                                @click="removeItem(item.product_id)"
                                class="p-1.5 text-gray-300 hover:text-gray-900 transition-colors"
                                title="Удалить"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Итого --}}
            <div class="lg:col-span-1">
                <div class="border border-gray-200 rounded-lg p-6 sticky top-28 space-y-6">
                    <h3 class="text-base font-semibold">Итого</h3>

                    {{-- Промокод --}}
                    <div>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="promocode"
                                placeholder="Промокод"
                                class="flex-1 px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:border-gray-400 transition-colors"
                                @keydown.enter.prevent="applyPromocode()"
                            >
                            <button
                                @click="applyPromocode()"
                                :disabled="!promocode || promoLoading"
                                class="px-3 py-2 border border-gray-900 text-gray-900 rounded text-sm font-medium hover:bg-gray-900 hover:text-white transition-colors disabled:opacity-50"
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
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Применен</span>
                            <button @click="removePromocode()" class="ml-auto text-gray-300 hover:text-gray-900 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Суммы --}}
                    <div class="space-y-3 border-t border-gray-100 pt-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Подитог</span>
                            <span class="font-medium" x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                        </div>
                        <div x-show="discount > 0" class="flex items-center justify-between text-sm">
                            <span class="text-green-600">Скидка</span>
                            <span class="font-medium text-green-600" x-text="'-' + formatPrice(discount) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-5">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Итого</span>
                            <span class="text-lg font-semibold" style="color: var(--primary);" x-text="formatPrice(total) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    <a
                        href="/store/{{ $store->slug }}/checkout"
                        class="block w-full py-3 text-center text-sm font-medium bg-gray-900 text-white rounded hover:bg-gray-800 transition-colors"
                    >
                        Оформить заказ
                    </a>

                    <a
                        href="/store/{{ $store->slug }}/catalog"
                        class="block text-center text-sm text-gray-400 hover:text-gray-900 transition-colors"
                    >
                        Продолжить покупки
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function minimalCartPage() {
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
                            detail: { message: 'Товар удален', type: 'success' }
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
