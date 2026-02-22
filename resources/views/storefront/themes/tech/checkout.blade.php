@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';
    $deliveryMethods = $store->activeDeliveryMethods;
    $paymentMethods = $store->activePaymentMethods;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-xs font-mono text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-1.5">/</span>
        <a href="/store/{{ $store->slug }}/cart" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Корзина</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-700">Оформление</span>
    </nav>

    <div class="flex items-center gap-3 mb-6">
        <div class="w-1 h-6 rounded-sm" style="background: var(--primary);"></div>
        <h1 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">Оформление заказа</h1>
    </div>

    {{-- Шаговый индикатор --}}
    <div class="mb-8">
        <div class="flex items-center justify-center gap-0 max-w-md mx-auto">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded flex items-center justify-center text-xs font-mono font-bold text-white" style="background: var(--primary);">1</span>
                <span class="text-xs font-semibold hidden sm:inline">Данные</span>
            </div>
            <div class="flex-1 h-px bg-gray-300 mx-3"></div>
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded flex items-center justify-center text-xs font-mono font-bold bg-gray-200 text-gray-500">2</span>
                <span class="text-xs font-semibold text-gray-400 hidden sm:inline">Доставка</span>
            </div>
            <div class="flex-1 h-px bg-gray-300 mx-3"></div>
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded flex items-center justify-center text-xs font-mono font-bold bg-gray-200 text-gray-500">3</span>
                <span class="text-xs font-semibold text-gray-400 hidden sm:inline">Оплата</span>
            </div>
        </div>
    </div>

    <div x-data="checkoutPage()" x-cloak>
        {{-- Загрузка --}}
        <div x-show="loading" class="flex items-center justify-center py-16">
            <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <div x-show="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Форма --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Контактные данные --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-900 text-white px-4 py-2.5 flex items-center gap-2">
                        <span class="text-xs font-mono font-bold">01</span>
                        <span class="text-sm font-semibold">Контактные данные</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Имя <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                x-model="form.customer_name"
                                class="w-full px-3 py-2 rounded border text-sm focus:outline-none focus:ring-1 focus:border-transparent transition-colors"
                                :class="errors.customer_name ? 'border-red-300 bg-red-50' : 'border-gray-200'"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="Ваше имя"
                            >
                            <p x-show="errors.customer_name" x-text="errors.customer_name" class="mt-1 text-xs text-red-500 font-mono"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Телефон <span class="text-red-500">*</span></label>
                            <input
                                type="tel"
                                x-model="form.customer_phone"
                                class="w-full px-3 py-2 rounded border text-sm focus:outline-none focus:ring-1 focus:border-transparent transition-colors"
                                :class="errors.customer_phone ? 'border-red-300 bg-red-50' : 'border-gray-200'"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="+998 XX XXX XX XX"
                            >
                            <p x-show="errors.customer_phone" x-text="errors.customer_phone" class="mt-1 text-xs text-red-500 font-mono"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Email</label>
                            <input
                                type="email"
                                x-model="form.customer_email"
                                class="w-full px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:ring-1 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                                placeholder="email@example.com"
                            >
                        </div>
                    </div>
                </div>

                {{-- Доставка --}}
                @if($deliveryMethods->isNotEmpty())
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-900 text-white px-4 py-2.5 flex items-center gap-2">
                            <span class="text-xs font-mono font-bold">02</span>
                            <span class="text-sm font-semibold">Способ доставки</span>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach($deliveryMethods as $method)
                                <label
                                    class="flex items-start gap-3 p-3 rounded-lg border-l-4 cursor-pointer transition-all duration-200"
                                    :class="form.delivery_method_id === {{ $method->id }}
                                        ? 'bg-gray-50'
                                        : 'border-transparent hover:bg-gray-50'"
                                    :style="form.delivery_method_id === {{ $method->id }} ? 'border-left-color: var(--primary)' : 'border-left-color: transparent'"
                                >
                                    <input
                                        type="radio"
                                        name="delivery_method"
                                        value="{{ $method->id }}"
                                        x-model.number="form.delivery_method_id"
                                        @change="deliveryPrice = {{ (float)$method->price }}; recalculate()"
                                        class="mt-0.5 accent-[var(--primary)]"
                                    >
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-900">{{ $method->name }}</span>
                                            <span class="text-sm font-bold font-mono" style="color: var(--primary);">
                                                @if((float)$method->price > 0)
                                                    {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                                @else
                                                    Бесплатно
                                                @endif
                                            </span>
                                        </div>
                                        @if($method->description)
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $method->description }}</p>
                                        @endif
                                        @if($method->min_days || $method->max_days)
                                            <p class="mt-0.5 text-xs text-gray-400 font-mono">{{ $method->getDeliveryDays() }}</p>
                                        @endif
                                        @if($method->free_from)
                                            <p class="mt-0.5 text-xs text-green-600 font-mono">Бесплатно от {{ number_format($method->free_from, 0, '.', ' ') }} {{ $currency }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="errors.delivery_method_id" x-text="errors.delivery_method_id" class="px-4 pb-3 text-xs text-red-500 font-mono"></p>

                        {{-- Адрес доставки --}}
                        <div x-show="form.delivery_method_id && !isPickup()" x-transition class="px-4 pb-4 space-y-3 border-t border-gray-100 pt-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Город</label>
                                    <input
                                        type="text"
                                        x-model="form.delivery_city"
                                        class="w-full px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:ring-1 focus:border-transparent"
                                        style="--tw-ring-color: var(--primary);"
                                        placeholder="Город"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Адрес</label>
                                    <input
                                        type="text"
                                        x-model="form.delivery_address"
                                        class="w-full px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:ring-1 focus:border-transparent"
                                        style="--tw-ring-color: var(--primary);"
                                        placeholder="Улица, дом, квартира"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1 font-mono">Комментарий</label>
                                <input
                                    type="text"
                                    x-model="form.delivery_comment"
                                    class="w-full px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:ring-1 focus:border-transparent"
                                    style="--tw-ring-color: var(--primary);"
                                    placeholder="Подъезд, этаж, код домофона..."
                                >
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Оплата --}}
                @if($paymentMethods->isNotEmpty())
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-900 text-white px-4 py-2.5 flex items-center gap-2">
                            <span class="text-xs font-mono font-bold">03</span>
                            <span class="text-sm font-semibold">Способ оплаты</span>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach($paymentMethods as $method)
                                <label
                                    class="flex items-start gap-3 p-3 rounded-lg border-l-4 cursor-pointer transition-all duration-200"
                                    :class="form.payment_method_id === {{ $method->id }}
                                        ? 'bg-gray-50'
                                        : 'border-transparent hover:bg-gray-50'"
                                    :style="form.payment_method_id === {{ $method->id }} ? 'border-left-color: var(--primary)' : 'border-left-color: transparent'"
                                >
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $method->id }}"
                                        x-model.number="form.payment_method_id"
                                        class="mt-0.5 accent-[var(--primary)]"
                                    >
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900">{{ $method->name }}</span>
                                        @if($method->description)
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $method->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="errors.payment_method_id" x-text="errors.payment_method_id" class="px-4 pb-3 text-xs text-red-500 font-mono"></p>
                    </div>
                @endif

                {{-- Комментарий --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2.5">
                        <span class="text-sm font-semibold">Комментарий к заказу</span>
                    </div>
                    <div class="p-4">
                        <textarea
                            x-model="form.customer_note"
                            rows="2"
                            class="w-full px-3 py-2 rounded border border-gray-200 text-sm focus:outline-none focus:ring-1 focus:border-transparent resize-none"
                            style="--tw-ring-color: var(--primary);"
                            placeholder="Пожелания к заказу..."
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- Сводка заказа --}}
            <div class="lg:col-span-1">
                <div class="border border-gray-200 rounded-lg sticky top-28">
                    <div class="bg-gray-900 text-white px-4 py-3">
                        <h3 class="text-sm font-bold uppercase tracking-wider">Ваш заказ</h3>
                    </div>

                    <div class="p-4 space-y-4">
                        {{-- Список товаров --}}
                        <div class="space-y-2 max-h-52 overflow-y-auto">
                            <template x-for="item in cart.items" :key="item.id">
                                <div class="flex items-center gap-2 text-xs">
                                    <div class="w-8 h-8 rounded border border-gray-200 bg-gray-100 overflow-hidden shrink-0">
                                        <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-contain">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-900 truncate font-medium" x-text="item.name"></p>
                                        <p class="text-gray-400 font-mono" x-text="item.quantity + ' x ' + formatPrice(item.price)"></p>
                                    </div>
                                    <span class="font-mono font-bold shrink-0" x-text="formatPrice(item.price * item.quantity)"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Промокод --}}
                        <div x-show="!cart.promocode" class="border-t border-gray-100 pt-3">
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    x-model="promocode"
                                    placeholder="Промокод"
                                    class="flex-1 px-2.5 py-1.5 rounded border border-gray-200 text-xs font-mono focus:outline-none focus:ring-1 focus:border-transparent"
                                    style="--tw-ring-color: var(--primary);"
                                >
                                <button
                                    @click="applyPromocode()"
                                    :disabled="!promocode"
                                    class="btn-primary px-2.5 py-1.5 rounded text-xs font-bold disabled:opacity-50"
                                >
                                    OK
                                </button>
                            </div>
                            <p x-show="promoError" x-text="promoError" class="mt-1 text-xs text-red-500 font-mono"></p>
                        </div>

                        {{-- Суммы --}}
                        <div class="space-y-1.5 border-t border-gray-100 pt-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 font-mono uppercase">Подитог</span>
                                <span class="font-mono font-semibold" x-text="formatPrice(cart.subtotal) + ' {{ $currency }}'"></span>
                            </div>
                            <div x-show="cart.discount > 0" class="flex items-center justify-between">
                                <span class="text-green-600 font-mono uppercase">Скидка</span>
                                <span class="font-mono font-semibold text-green-600" x-text="'-' + formatPrice(cart.discount) + ' {{ $currency }}'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 font-mono uppercase">Доставка</span>
                                <span class="font-mono font-semibold" x-text="deliveryPrice > 0 ? formatPrice(deliveryPrice) + ' {{ $currency }}' : 'Бесплатно'"></span>
                            </div>
                        </div>

                        <div class="border-l-4 pl-3 py-2" style="border-color: var(--primary);">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider">Итого</span>
                                <span class="text-lg font-bold font-mono" style="color: var(--primary);" x-text="formatPrice(grandTotal) + ' {{ $currency }}'"></span>
                            </div>
                        </div>

                        {{-- Ошибка общая --}}
                        <div x-show="generalError" class="p-2.5 rounded bg-red-50 border border-red-200">
                            <p class="text-xs text-red-600 font-mono" x-text="generalError"></p>
                        </div>

                        <button
                            @click="submitOrder()"
                            :disabled="submitting"
                            class="w-full btn-primary py-3 rounded-lg text-sm font-bold uppercase tracking-wider flex items-center justify-center gap-2 disabled:opacity-50 transition-all duration-200 hover:shadow-lg"
                        >
                            <template x-if="!submitting">
                                <span>Оформить заказ</span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
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
                        const json = await response.json();
                        this.cart = json.data || json;
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
                        const orderData = data.data || data;
                        // Инициируем оплату если есть order ID
                        if (orderData.id) {
                            try {
                                const payRes = await fetch(`/store/${slug}/api/payment/${orderData.id}/initiate`, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                    },
                                });
                                const payJson = await payRes.json();
                                const payData = payJson.data || payJson;
                                if (payData.payment_url) {
                                    window.location.href = payData.payment_url;
                                    return;
                                }
                            } catch (e) {
                                console.warn('Payment initiation failed, redirecting to order page', e);
                            }
                        }
                        window.location.href = `/store/${slug}/order/${orderData.order_number}`;
                    } else if (response.status === 422) {
                        this.errors = {};
                        if (data.errors && Array.isArray(data.errors)) {
                            // ApiResponder format: [{code, message, field}]
                            for (const err of data.errors) {
                                if (err.field) {
                                    this.errors[err.field] = err.message;
                                }
                            }
                            this.generalError = data.errors[0]?.message || '';
                        } else if (data.errors && typeof data.errors === 'object') {
                            // Laravel validation format: {field: [messages]}
                            for (const [key, messages] of Object.entries(data.errors)) {
                                this.errors[key] = Array.isArray(messages) ? messages[0] : messages;
                            }
                            this.generalError = data.message || '';
                        } else {
                            this.generalError = data.message || data.errors?.[0]?.message || 'Ошибка оформления заказа';
                        }
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
