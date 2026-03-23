{{-- Buy in 1 Click — быстрый заказ без оформления --}}
@php $currency = $store->currency ?? 'сум'; @endphp

<div
    x-data="buyOneClick()"
    x-on:buy-one-click.window="open($event.detail)"
    x-cloak
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50"
    @keydown.escape.window="visible && !success ? close() : null"
>
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="!success ? close() : null"></div>

    {{-- Modal --}}
    <div class="relative flex items-end sm:items-center justify-center min-h-full p-0 sm:p-4">
        <div
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full sm:translate-y-8 sm:scale-95"
            x-transition:enter-end="translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 sm:scale-100"
            x-transition:leave-end="translate-y-full sm:translate-y-8 sm:scale-95"
            class="relative bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[90vh] overflow-y-auto shadow-2xl"
            @click.stop
        >
            {{-- Закрыть --}}
            <button x-show="!success" @click="close()" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Форма заказа --}}
            <div x-show="!success" class="p-5 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-5">Купить в 1 клик</h3>

                {{-- Товар --}}
                <div class="flex gap-3 p-3 bg-gray-50 rounded-xl mb-5">
                    <div class="w-16 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                        <img x-show="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 line-clamp-2" x-text="product.name"></p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-sm font-bold" style="color: var(--primary);" x-text="formatPrice(product.price)"></span>
                            <span x-show="product.quantity > 1" class="text-xs text-gray-400" x-text="'× ' + product.quantity + ' шт.'"></span>
                        </div>
                    </div>
                </div>

                {{-- Поля формы --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ваше имя *</label>
                        <input
                            type="text"
                            x-model="form.name"
                            placeholder="Введите имя"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: var(--primary);"
                            :class="errors.name ? 'border-red-300 ring-red-200' : ''"
                        >
                        <p x-show="errors.name" class="text-xs text-red-500 mt-1" x-text="errors.name"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Телефон *</label>
                        <input
                            type="tel"
                            x-model="form.phone"
                            placeholder="+998 __ ___ __ __"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: var(--primary);"
                            :class="errors.phone ? 'border-red-300 ring-red-200' : ''"
                        >
                        <p x-show="errors.phone" class="text-xs text-red-500 mt-1" x-text="errors.phone"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Комментарий</label>
                        <textarea
                            x-model="form.comment"
                            placeholder="Пожелания к заказу..."
                            rows="2"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                            style="--tw-ring-color: var(--primary);"
                        ></textarea>
                    </div>
                </div>

                {{-- Ошибка API --}}
                <div x-show="apiError" class="mt-4 p-3 bg-red-50 border border-red-100 rounded-xl">
                    <p class="text-sm text-red-600" x-text="apiError"></p>
                </div>

                {{-- Кнопки --}}
                <div class="mt-6 flex gap-3">
                    <button
                        @click="submit()"
                        :disabled="loading"
                        class="flex-1 btn-primary py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <template x-if="!loading">
                            <span>Оформить заказ</span>
                        </template>
                        <template x-if="loading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </template>
                    </button>
                    <button @click="close()" class="px-5 py-3 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors">
                        Отмена
                    </button>
                </div>
            </div>

            {{-- Успех --}}
            <div x-show="success" class="p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5 animate-checkmark">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Заказ принят!</h3>
                <p class="text-gray-500 mb-1">
                    Номер заказа: <span class="font-semibold text-gray-900" x-text="'#' + orderNumber"></span>
                </p>
                <p class="text-sm text-gray-400 mb-6">Мы перезвоним вам для подтверждения</p>
                <button @click="close()" class="btn-primary px-8 py-2.5 rounded-xl text-sm font-semibold">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function buyOneClick() {
        return {
            visible: false,
            loading: false,
            success: false,
            orderNumber: '',
            apiError: '',
            product: { id: null, variantId: null, name: '', price: 0, image: null, slug: '', quantity: 1 },
            form: { name: '', phone: '', comment: '' },
            errors: { name: '', phone: '' },

            open(detail) {
                this.product = {
                    id: detail.productId,
                    variantId: detail.variantId || null,
                    name: detail.name || '',
                    price: detail.price || 0,
                    image: detail.image || null,
                    slug: detail.slug || '{{ $store->slug }}',
                    quantity: detail.quantity || 1,
                };
                this.form = { name: '', phone: '', comment: '' };
                this.errors = { name: '', phone: '' };
                this.loading = false;
                this.success = false;
                this.apiError = '';
                this.orderNumber = '';
                this.visible = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.visible = false;
                document.body.style.overflow = '';
            },

            formatPrice(val) {
                if (!val) return '';
                return new Intl.NumberFormat('ru-RU').format(Math.round(val)) + ' {{ $currency }}';
            },

            validate() {
                this.errors = { name: '', phone: '' };
                let valid = true;

                if (!this.form.name.trim()) {
                    this.errors.name = 'Введите имя';
                    valid = false;
                }

                const digits = this.form.phone.replace(/\D/g, '');
                if (digits.length < 9) {
                    this.errors.phone = 'Введите корректный номер телефона';
                    valid = false;
                }

                return valid;
            },

            async submit() {
                if (!this.validate()) return;

                this.loading = true;
                this.apiError = '';

                try {
                    const slug = this.product.slug;
                    const resp = await fetch(`/store/${slug}/api/quick-order`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            customer_name: this.form.name.trim(),
                            customer_phone: this.form.phone.trim(),
                            customer_note: this.form.comment.trim() || 'Быстрый заказ (1 клик)',
                            product_id: this.product.id,
                            variant_id: this.product.variantId,
                            quantity: this.product.quantity,
                        }),
                    });

                    const data = await resp.json();

                    if (resp.ok) {
                        this.orderNumber = data.data?.order_number || data.order_number || '—';
                        this.success = true;
                    } else {
                        this.apiError = data.message || 'Произошла ошибка при оформлении заказа';
                    }
                } catch {
                    this.apiError = 'Ошибка соединения. Попробуйте позже.';
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
