@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
    $deliveryMethods = $store->activeDeliveryMethods;
    $paymentMethods = $store->activePaymentMethods;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <a href="/store/{{ $store->slug }}/cart" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Корзина</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Оформление заказа</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-8">Оформление заказа</h1>

    <div x-data="checkoutPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <svg class="w-8 h-8 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <div x-show="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Форма --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Контактные данные --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-5 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full text-white text-sm flex items-center justify-center font-bold" style="background: var(--primary);">1</span>
                        Контактные данные
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Имя <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                x-model="form.customer_name"
                                class="w-full px-4 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
                                :class="errors.customer_name ? 'border-red-300 bg-red-50' : 'border-gray-200'"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="Ваше имя"
                            >
                            <p x-show="errors.customer_name" x-text="errors.customer_name" class="mt-1 text-xs text-red-500"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Телефон <span class="text-red-500">*</span></label>
                            <input
                                type="tel"
                                x-model="form.customer_phone"
                                class="w-full px-4 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
                                :class="errors.customer_phone ? 'border-red-300 bg-red-50' : 'border-gray-200'"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="+998 XX XXX XX XX"
                            >
                            <p x-show="errors.customer_phone" x-text="errors.customer_phone" class="mt-1 text-xs text-red-500"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input
                                type="email"
                                x-model="form.customer_email"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="email@example.com"
                            >
                        </div>
                    </div>
                </div>

                {{-- Доставка --}}
                @if($deliveryMethods->isNotEmpty())
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 class="text-lg font-semibold mb-5 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full text-white text-sm flex items-center justify-center font-bold" style="background: var(--primary);">2</span>
                            Способ доставки
                        </h2>
                        <div class="space-y-3">
                            @foreach($deliveryMethods as $method)
                                <label
                                    class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                                    :class="form.delivery_method_id === {{ $method->id }} ? 'border-(--primary) bg-blue-50/30' : 'border-gray-100 hover:border-gray-200'"
                                >
                                    <input
                                        type="radio"
                                        name="delivery_method"
                                        value="{{ $method->id }}"
                                        x-model.number="form.delivery_method_id"
                                        @change="deliveryPrice = {{ (float)$method->price }}; recalculate()"
                                        class="mt-0.5 accent-(--primary)"
                                    >
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900">{{ $method->name }}</span>
                                            <span class="text-sm font-semibold" style="color: var(--primary);">
                                                @if((float)$method->price > 0)
                                                    {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                                @else
                                                    Бесплатно
                                                @endif
                                            </span>
                                        </div>
                                        @if($method->description)
                                            <p class="mt-1 text-xs text-gray-500">{{ $method->description }}</p>
                                        @endif
                                        @if($method->min_days || $method->max_days)
                                            <p class="mt-1 text-xs text-gray-400">Срок: {{ $method->getDeliveryDays() }}</p>
                                        @endif
                                        @if($method->free_from)
                                            <p class="mt-1 text-xs text-green-600">Бесплатно от {{ number_format($method->free_from, 0, '.', ' ') }} {{ $currency }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="errors.delivery_method_id" x-text="errors.delivery_method_id" class="mt-2 text-xs text-red-500"></p>

                        {{-- Адрес доставки --}}
                        <div x-show="form.delivery_method_id && !isPickup()" x-transition class="mt-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Город</label>
                                    <input
                                        type="text"
                                        x-model="form.delivery_city"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                        style="--tw-ring-color: var(--primary);"
                                        placeholder="Город"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Адрес</label>
                                    <input
                                        type="text"
                                        x-model="form.delivery_address"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                        style="--tw-ring-color: var(--primary);"
                                        placeholder="Улица, дом, квартира"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Комментарий к доставке</label>
                                <input
                                    type="text"
                                    x-model="form.delivery_comment"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: var(--primary);"
                                    placeholder="Подъезд, этаж, код домофона..."
                                >
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Оплата --}}
                @if($paymentMethods->isNotEmpty())
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 class="text-lg font-semibold mb-5 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full text-white text-sm flex items-center justify-center font-bold" style="background: var(--primary);">3</span>
                            Способ оплаты
                        </h2>
                        <div class="space-y-3">
                            @foreach($paymentMethods as $method)
                                <label
                                    class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                                    :class="form.payment_method_id === {{ $method->id }} ? 'border-(--primary) bg-blue-50/30' : 'border-gray-100 hover:border-gray-200'"
                                >
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $method->id }}"
                                        x-model.number="form.payment_method_id"
                                        class="mt-0.5 accent-(--primary)"
                                    >
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">{{ $method->name }}</span>
                                        @if($method->description)
                                            <p class="mt-1 text-xs text-gray-500">{{ $method->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="errors.payment_method_id" x-text="errors.payment_method_id" class="mt-2 text-xs text-red-500"></p>
                    </div>
                @endif

                {{-- Комментарий --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Комментарий к заказу</h2>
                    <textarea
                        x-model="form.customer_note"
                        rows="3"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                        style="--tw-ring-color: var(--primary);"
                        placeholder="Пожелания к заказу..."
                    ></textarea>
                </div>
            </div>

            {{-- Сводка заказа --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-28 space-y-5">
                    <h3 class="font-semibold text-lg">Ваш заказ</h3>

                    {{-- Список товаров --}}
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <template x-for="item in cart.items" :key="item.id">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                    <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 truncate" x-text="item.name"></p>
                                    <p class="text-xs text-gray-500" x-text="item.quantity + ' x ' + formatPrice(item.price) + ' {{ $currency }}'"></p>
                                </div>
                                <span class="text-sm font-medium shrink-0" x-text="formatPrice(item.price * item.quantity) + ' {{ $currency }}'"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Промокод --}}
                    <div x-show="!cart.promocode">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="promocode"
                                placeholder="Промокод"
                                class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                            >
                            <button
                                @click="applyPromocode()"
                                :disabled="!promocode"
                                class="btn-primary px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
                            >
                                OK
                            </button>
                        </div>
                        <p x-show="promoError" x-text="promoError" class="mt-1 text-xs text-red-500"></p>
                    </div>

                    {{-- Суммы --}}
                    <div class="space-y-2.5 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Подитог</span>
                            <span class="font-medium" x-text="formatPrice(cart.subtotal) + ' {{ $currency }}'"></span>
                        </div>
                        <div x-show="cart.discount > 0" class="flex items-center justify-between text-sm">
                            <span class="text-green-600">Скидка</span>
                            <span class="font-medium text-green-600" x-text="'-' + formatPrice(cart.discount) + ' {{ $currency }}'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Доставка</span>
                            <span class="font-medium" x-text="deliveryPrice > 0 ? formatPrice(deliveryPrice) + ' {{ $currency }}' : 'Бесплатно'"></span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-semibold">Итого</span>
                            <span class="text-xl font-bold" style="color: var(--primary);" x-text="formatPrice(grandTotal) + ' {{ $currency }}'"></span>
                        </div>
                    </div>

                    {{-- Ошибка общая --}}
                    <div x-show="generalError" class="p-3 rounded-xl bg-red-50 border border-red-100">
                        <p class="text-sm text-red-600" x-text="generalError"></p>
                    </div>

                    <button
                        @click="submitOrder()"
                        :disabled="submitting"
                        class="w-full btn-primary py-3.5 rounded-xl text-base font-semibold flex items-center justify-center gap-2 disabled:opacity-50 transition-all duration-200 hover:shadow-lg"
                    >
                        <template x-if="!submitting">
                            <span>Оформить заказ</span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Оформляем...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function checkoutPage() {
        return {
            loading: true,
            submitting: false,
            cart: { items: [], subtotal: 0, discount: 0, total: 0, promocode: null },
            deliveryPrice: 0,
            promocode: '',
            promoError: '',
            generalError: '',
            errors: {},
            form: {
                customer_name: '',
                customer_phone: '',
                customer_email: '',
                delivery_method_id: {{ $deliveryMethods->first()?->id ?? 'null' }},
                delivery_address: '',
                delivery_city: '',
                delivery_comment: '',
                payment_method_id: {{ $paymentMethods->first()?->id ?? 'null' }},
                customer_note: '',
            },

            get grandTotal() {
                return (this.cart.total || 0) + this.deliveryPrice;
            },

            async init() {
                await this.fetchCart();

                // Установить начальную стоимость доставки
                @if($deliveryMethods->first())
                    this.deliveryPrice = {{ (float)$deliveryMethods->first()->price }};
                @endif
            },

            isPickup() {
                const pickupTypes = ['pickup', 'self_pickup'];
                @foreach($deliveryMethods as $method)
                    if (this.form.delivery_method_id === {{ $method->id }} && pickupTypes.includes('{{ $method->type }}')) {
                        return true;
                    }
                @endforeach
                return false;
            },

            recalculate() {
                // Проверяем бесплатную доставку
                @foreach($deliveryMethods as $method)
                    @if($method->free_from)
                        if (this.form.delivery_method_id === {{ $method->id }} && this.cart.subtotal >= {{ (float)$method->free_from }}) {
                            this.deliveryPrice = 0;
                            return;
                        }
                    @endif
                @endforeach
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
                        this.cart = await response.json();
                        if (!this.cart.items || this.cart.items.length === 0) {
                            window.location.href = `/store/{{ $store->slug }}/cart`;
                            return;
                        }
                    }
                } catch (e) {
                    // Ignore
                } finally {
                    this.loading = false;
                }
            },

            async applyPromocode() {
                if (!this.promocode) return;
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
                        await this.fetchCart();
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Промокод применен', type: 'success' }
                        }));
                    } else {
                        this.promoError = data.message || 'Недействительный промокод';
                    }
                } catch (e) {
                    this.promoError = 'Ошибка соединения';
                }
            },

            validate() {
                this.errors = {};
                if (!this.form.customer_name?.trim()) {
                    this.errors.customer_name = 'Введите имя';
                }
                if (!this.form.customer_phone?.trim()) {
                    this.errors.customer_phone = 'Введите телефон';
                }
                @if($deliveryMethods->isNotEmpty())
                    if (!this.form.delivery_method_id) {
                        this.errors.delivery_method_id = 'Выберите способ доставки';
                    }
                @endif
                @if($paymentMethods->isNotEmpty())
                    if (!this.form.payment_method_id) {
                        this.errors.payment_method_id = 'Выберите способ оплаты';
                    }
                @endif
                return Object.keys(this.errors).length === 0;
            },

            async submitOrder() {
                if (!this.validate()) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Заполните обязательные поля', type: 'error' }
                    }));
                    return;
                }

                this.submitting = true;
                this.generalError = '';

                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/checkout`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.location.href = data.redirect || `/store/${slug}/order/${data.order_number}`;
                    } else if (response.status === 422) {
                        this.errors = {};
                        if (data.errors) {
                            for (const [key, messages] of Object.entries(data.errors)) {
                                this.errors[key] = Array.isArray(messages) ? messages[0] : messages;
                            }
                        }
                        this.generalError = data.message || '';
                    } else {
                        this.generalError = data.message || 'Произошла ошибка при оформлении заказа';
                    }
                } catch (e) {
                    this.generalError = 'Ошибка соединения. Попробуйте снова.';
                } finally {
                    this.submitting = false;
                }
            },
        }
    }
</script>
@endsection
