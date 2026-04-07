@extends('storefront.layouts.app')

@section('page_title', 'Оформление заказа — ' . $store->name)

@section('meta')
<script nonce="{{ $cspNonce ?? '' }}" src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.api_key', '') }}&lang=ru_RU"></script>
@endsection

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
                               :class="form.delivery_method_id == dm.id ? 'border-primary bg-primary/10' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" :value="dm.id" x-model.number="form.delivery_method_id" class="accent-primary">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900" x-text="dm.name"></span>
                                <span x-show="dm.price > 0" class="ml-2 text-sm text-gray-500" x-text="formatPrice(dm.price) + ' {{ $currency }}'"></span>
                                <span x-show="!dm.price || dm.price == 0" class="ml-2 text-sm text-green-600">Бесплатно</span>
                            </div>
                        </label>
                    </template>
                </div>
                {{-- Адрес доставки с Яндекс.Картой --}}
                <div x-data="addressPicker()" x-init="initMap()">
                    <label class="block text-sm text-gray-600 mb-1">Адрес доставки</label>
                    <div class="relative">
                        <input type="text"
                               x-model="addressQuery"
                               @input.debounce.400ms="searchAddress()"
                               @focus="showSuggestions = suggestions.length > 0"
                               placeholder="Введите адрес или укажите на карте"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent pr-10"
                               style="--tw-ring-color: var(--primary);">
                        <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>

                        {{-- Подсказки адресов --}}
                        <div x-show="showSuggestions && suggestions.length > 0"
                             @click.away="showSuggestions = false"
                             x-cloak
                             class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="(s, i) in suggestions" :key="i">
                                <button @click="selectSuggestion(s)"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 border-b border-gray-50 last:border-0 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <span class="text-gray-700" x-text="s.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Карта --}}
                    <div id="delivery-map"
                         class="mt-3 rounded-xl overflow-hidden border border-gray-200"
                         style="height: 250px; background: #f3f4f6;">
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm" x-show="!mapReady">
                            Загрузка карты...
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Нажмите на карту чтобы указать точку доставки</p>

                    {{-- Город (извлекается автоматически) --}}
                    <input type="hidden" x-model="city">
                </div>
            </div>

            {{-- Оплата --}}
            <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
                <h2 class="text-base font-bold text-gray-900">Оплата</h2>
                <div class="space-y-2">
                    <template x-for="pm in paymentMethods" :key="pm.id">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                               :class="form.payment_method_id == pm.id ? 'border-primary bg-primary/10' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" :value="pm.id" x-model.number="form.payment_method_id" class="accent-primary">
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
            delivery_city: '',
            customer_note: '',
        },

        init() {
            // Слушаем выбор адреса из карты
            window.addEventListener('address-selected', (e) => {
                if (e.detail) {
                    this.form.delivery_address = e.detail.address || '';
                    this.form.delivery_city = e.detail.city || '';
                }
            });
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

function addressPicker() {
    return {
        addressQuery: '',
        suggestions: [],
        showSuggestions: false,
        city: '',
        map: null,
        placemark: null,
        mapReady: false,

        initMap() {
            if (typeof ymaps === 'undefined') {
                // Яндекс.Карты не загружены — fallback без карты
                console.log('Yandex Maps API not loaded');
                return;
            }

            ymaps.ready(() => {
                // Ташкент по умолчанию
                this.map = new ymaps.Map('delivery-map', {
                    center: [41.2995, 69.2401],
                    zoom: 12,
                    controls: ['zoomControl', 'geolocationControl']
                });

                this.mapReady = true;

                // Клик по карте — установить маркер
                this.map.events.add('click', (e) => {
                    const coords = e.get('coords');
                    this.setMarker(coords);
                    this.reverseGeocode(coords);
                });

                // Геолокация пользователя
                const geolocation = ymaps.geolocation;
                geolocation.get({ provider: 'browser', mapStateAutoApply: false }).then((result) => {
                    const coords = result.geoObjects.get(0).geometry.getCoordinates();
                    this.map.setCenter(coords, 14);
                });
            });
        },

        setMarker(coords) {
            if (this.placemark) {
                this.placemark.geometry.setCoordinates(coords);
            } else {
                this.placemark = new ymaps.Placemark(coords, {}, {
                    preset: 'islands#redDotIcon',
                    draggable: true,
                });
                this.placemark.events.add('dragend', () => {
                    const newCoords = this.placemark.geometry.getCoordinates();
                    this.reverseGeocode(newCoords);
                });
                this.map.geoObjects.add(this.placemark);
            }
        },

        reverseGeocode(coords) {
            ymaps.geocode(coords).then((res) => {
                const firstGeoObject = res.geoObjects.get(0);
                if (firstGeoObject) {
                    const address = firstGeoObject.getAddressLine();
                    const city = firstGeoObject.getLocalities().join(', ') ||
                                 firstGeoObject.getAdministrativeAreas().join(', ');

                    this.addressQuery = address;
                    this.city = city;

                    // Обновить форму чекаута
                    this.syncToForm(address, city);
                }
            });
        },

        searchAddress() {
            if (this.addressQuery.length < 3) {
                this.suggestions = [];
                return;
            }

            if (typeof ymaps === 'undefined') return;

            ymaps.geocode(this.addressQuery, { results: 5 }).then((res) => {
                this.suggestions = [];
                res.geoObjects.each((obj) => {
                    this.suggestions.push({
                        name: obj.getAddressLine(),
                        coords: obj.geometry.getCoordinates(),
                        city: obj.getLocalities().join(', ') || obj.getAdministrativeAreas().join(', '),
                    });
                });
                this.showSuggestions = this.suggestions.length > 0;
            });
        },

        selectSuggestion(s) {
            this.addressQuery = s.name;
            this.city = s.city;
            this.showSuggestions = false;

            if (this.map) {
                this.map.setCenter(s.coords, 16);
                this.setMarker(s.coords);
            }

            this.syncToForm(s.name, s.city);
        },

        syncToForm(address, city) {
            // Обновляем форму mpCheckout через DOM
            const el = document.querySelector('[x-data*="mpCheckout"]');
            if (el && el.__x) {
                el.__x.$data.form.delivery_address = address;
                el.__x.$data.form.delivery_city = city;
            }
            // Также через Alpine.$data если доступен
            this.$dispatch('address-selected', { address, city });
        }
    }
}
</script>
@endsection
