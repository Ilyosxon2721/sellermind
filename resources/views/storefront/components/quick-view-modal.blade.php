{{-- Quick View Modal — быстрый просмотр товара из каталога --}}
<div
    x-data="quickViewModal()"
    x-on:quick-view.window="open($event.detail)"
    x-cloak
    class="fixed inset-0 z-50"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="close()"
>
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

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
            class="relative bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl"
            @click.stop
        >
            {{-- Закрыть --}}
            <button @click="close()" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-gray-500 hover:bg-white hover:text-gray-800 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Изображение --}}
            <div class="aspect-square bg-gray-100 overflow-hidden rounded-t-2xl sm:rounded-t-2xl">
                <img
                    x-show="product.image"
                    :src="product.image"
                    :alt="product.name"
                    class="w-full h-full object-contain"
                >
                <div x-show="!product.image" class="w-full h-full flex items-center justify-center text-gray-300">
                    <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>

                {{-- Скидка --}}
                <div x-show="product.oldPrice && product.oldPrice > product.price" class="absolute top-3 left-3">
                    <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-red-500 text-white"
                        x-text="'-' + Math.round((1 - product.price / product.oldPrice) * 100) + '%'"
                    ></span>
                </div>
            </div>

            {{-- Контент --}}
            <div class="p-5 space-y-4">
                <h3 class="text-lg font-bold text-gray-900 leading-tight" x-text="product.name"></h3>

                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold" style="color: var(--primary);" x-text="formatPrice(product.price)"></span>
                    <span x-show="product.oldPrice && product.oldPrice > product.price" class="text-sm text-gray-400 line-through" x-text="formatPrice(product.oldPrice)"></span>
                </div>

                {{-- Наличие --}}
                <div>
                    <template x-if="product.stock > 0 && product.stock < 5">
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-600">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span x-text="'Осталось ' + product.stock + ' шт.'"></span>
                        </span>
                    </template>
                    <template x-if="product.stock >= 5">
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            В наличии
                        </span>
                    </template>
                    <template x-if="product.stock <= 0">
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            Нет в наличии
                        </span>
                    </template>
                </div>

                {{-- Количество + Корзина --}}
                <div class="flex items-center gap-3">
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                        <button @click="qty > 1 ? qty-- : null" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </button>
                        <input type="number" x-model.number="qty" min="1" max="99" class="w-12 h-10 text-center text-sm font-medium border-x border-gray-200 focus:outline-none">
                        <button @click="qty < 99 ? qty++ : null" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    <button
                        x-show="product.stock > 0"
                        @click="addToCart()"
                        :disabled="loading"
                        class="flex-1 btn-primary py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <template x-if="!loading">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                В корзину
                            </span>
                        </template>
                        <template x-if="loading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </template>
                    </button>
                </div>

                {{-- Ссылка на полную страницу --}}
                <a :href="product.url" class="block text-center text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Подробнее о товаре →
                </a>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function quickViewModal() {
        return {
            visible: false,
            loading: false,
            qty: 1,
            product: { id: null, name: '', price: 0, oldPrice: null, image: null, slug: '', stock: 0, url: '' },

            open(detail) {
                this.product = {
                    id: detail.id,
                    name: detail.name || '',
                    price: detail.price || 0,
                    oldPrice: detail.oldPrice || null,
                    image: detail.image || null,
                    slug: detail.slug || '',
                    stock: detail.stock ?? 99,
                    url: detail.url || `/store/${detail.slug}/product/${detail.id}`,
                };
                this.qty = 1;
                this.loading = false;
                this.visible = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.visible = false;
                document.body.style.overflow = '';
            },

            formatPrice(val) {
                if (!val) return '';
                return new Intl.NumberFormat('ru-RU').format(Math.round(val)) + ' {{ $store->currency ?? "сум" }}';
            },

            async addToCart() {
                this.loading = true;
                try {
                    const resp = await fetch(`/store/${this.product.slug}/api/cart/add`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ product_id: this.product.id, quantity: this.qty }),
                    });
                    if (resp.ok) {
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Товар добавлен в корзину', type: 'success' } }));
                        this.close();
                    } else {
                        const data = await resp.json();
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Ошибка', type: 'error' } }));
                    }
                } catch {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Ошибка соединения', type: 'error' } }));
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
