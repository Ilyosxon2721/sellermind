@extends('storefront.layouts.app')

@section('page_title', 'Оформление заказа — ' . $store->name)

@section('content')
@php $slug = $store->slug; $currency = $store->currency ?? 'сум'; @endphp

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-8" x-data="mpCheckout('{{ $slug }}', {{ json_encode(array_values($cart)) }}, {{ json_encode($store->activeDeliveryMethods) }}, {{ json_encode($store->activePaymentMethods) }})">

    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Оформление заказа</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Форма --}}
        <form @submit.prevent="submit()" class="lg:col-span-2 space-y-5">
            {{-- Контактные данные --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
                <h2 class="text-base font-bold text-gray-900">Контактные данные</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Имя *</label>
                        <input type="text" x-model="form.customer_name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Телефон *</label>
                        <input type="tel" x-model="form.customer_phone" required placeholder="+998" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Email</label>
                    <input type="email" x-model="form.customer_email" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                </div>
            </div>

            {{-- Доставка --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
                <h2 class="text-base font-bold text-gray-900">Доставка</h2>
                <div class="space-y-2">
                    <template x-for="dm in deliveryMethods" :key="dm.id">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                               :class="form.delivery_method_id == dm.id ? 'border-purple-500 bg-purple-50' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" :value="dm.id" x-model.number="form.delivery_method_id" class="accent-purple-600">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900" x-text="dm.name"></span>
                                <span x-show="dm.price > 0" class="ml-2 text-sm text-gray-500" x-text="formatPrice(dm.price) + ' {{ $currency }}'"></span>
                                <span x-show="!dm.price || dm.price == 0" class="ml-2 text-sm text-green-600">Бесплатно</span>
                            </div>
                        </label>
                    </template>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Адрес доставки</label>
                    <input type="text" x-model="form.delivery_address" placeholder="Город, улица, дом, квартира" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                </div>
            </div>

            {{-- Оплата --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
                <h2 class="text-base font-bold text-gray-900">Оплата</h2>
                <div class="space-y-2">
                    <template x-for="pm in paymentMethods" :key="pm.id">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                               :class="form.payment_method_id == pm.id ? 'border-purple-500 bg-purple-50' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" :value="pm.id" x-model.number="form.payment_method_id" class="accent-purple-600">
                            <span class="text-sm font-medium text-gray-900" x-text="pm.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Комментарий --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <label class="block text-sm text-gray-600 mb-1">Комментарий к заказу</label>
                <textarea x-model="form.customer_note" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);"></textarea>
            </div>

            <p x-show="error" x-text="error" class="text-sm text-red-600 font-medium"></p>

            <button type="submit" :disabled="submitting" class="w-full py-3.5 rounded-xl text-white font-bold text-base transition-all hover:opacity-90 disabled:opacity-50" style="background: var(--primary);">
                <span x-show="!submitting">Оформить заказ</span>
                <span x-show="submitting">Оформляем...</span>
            </button>
        </form>

        {{-- Итого --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-100 p-5 sticky top-24 space-y-3">
                <h3 class="text-base font-bold text-gray-900">Ваш заказ</h3>
                <template x-for="item in cartItems" :key="item.product_id">
                    <div class="flex justify-between text-sm py-1.5 border-b border-gray-50">
                        <span class="text-gray-600 truncate mr-2" x-text="item.name + ' × ' + item.quantity"></span>
                        <span class="font-medium whitespace-nowrap" x-text="formatPrice(item.price * item.quantity)"></span>
                    </div>
                </template>
                <div class="flex justify-between text-sm pt-2">
                    <span class="text-gray-500">Доставка</span>
                    <span class="font-medium" x-text="deliveryPrice > 0 ? formatPrice(deliveryPrice) + ' {{ $currency }}' : 'Бесплатно'"></span>
                </div>
                <div class="flex justify-between text-lg font-bold pt-3 border-t border-gray-100">
                    <span>Итого</span>
                    <span x-text="formatPrice(total) + ' {{ $currency }}'"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function mpCheckout(slug, cartItems, deliveryMethods, paymentMethods) {
    return {
        cartItems,
        deliveryMethods,
        paymentMethods,
        submitting: false,
        error: '',
        form: {
            customer_name: '',
            customer_phone: '',
            customer_email: '',
            delivery_method_id: deliveryMethods[0]?.id || '',
            payment_method_id: paymentMethods[0]?.id || '',
            delivery_address: '',
            customer_note: '',
        },
        get subtotal() { return this.cartItems.reduce((s, i) => s + i.price * i.quantity, 0); },
        get deliveryPrice() {
            const dm = this.deliveryMethods.find(d => d.id == this.form.delivery_method_id);
            if (!dm || !dm.price) return 0;
            if (dm.free_from && this.subtotal >= parseFloat(dm.free_from)) return 0;
            return parseFloat(dm.price);
        },
        get total() { return this.subtotal + this.deliveryPrice; },

        async submit() {
            this.submitting = true;
            this.error = '';
            try {
                const r = await fetch(`/store/${slug}/api/checkout`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await r.json();
                if (r.ok) {
                    window.location.href = `/store/${slug}/order/${data.data.order_number}`;
                } else {
                    this.error = data.message || Object.values(data.errors || {})[0]?.[0] || 'Ошибка оформления';
                }
            } catch (e) {
                this.error = 'Ошибка сети';
            }
            this.submitting = false;
        }
    }
}
</script>
@endsection
