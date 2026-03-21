{{-- Wishlist Page Content — содержимое страницы избранного --}}
@php $currency = $store->currency ?? 'сум'; @endphp

<div x-data="wishlistPage()" x-init="$nextTick(() => loadItems())">
    {{-- Пустое состояние --}}
    <div x-show="items.length === 0" class="text-center py-20">
        <svg class="w-24 h-24 mx-auto text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Список избранного пуст</h2>
        <p class="text-gray-500 mb-8">Добавляйте товары, нажимая на сердечко</p>
        <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-8 py-3 rounded-xl text-sm font-semibold inline-block">
            Перейти в каталог
        </a>
    </div>

    {{-- Сетка товаров --}}
    <div x-show="items.length > 0">
        <p class="text-sm text-gray-500 mb-6">
            В избранном: <span class="font-medium text-gray-900" x-text="items.length"></span> {{ trans_choice('товар|товара|товаров', 1) }}
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <template x-for="item in items" :key="item.id">
                <div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                    <a :href="item.url" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img
                                x-show="item.image"
                                :src="item.image"
                                :alt="item.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            >
                            <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>

                            {{-- Скидка --}}
                            <div x-show="item.oldPrice && item.oldPrice > item.price" class="absolute top-3 left-3">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-red-500 text-white"
                                    x-text="'-' + Math.round((1 - item.price / item.oldPrice) * 100) + '%'"
                                ></span>
                            </div>
                        </div>
                    </a>

                    <div class="p-4">
                        <a :href="item.url" class="block">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-gray-600 transition-colors" x-text="item.name"></h3>
                        </a>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-lg font-bold" style="color: var(--primary);" x-text="formatPrice(item.price)"></span>
                            <span x-show="item.oldPrice && item.oldPrice > item.price" class="text-xs text-gray-400 line-through" x-text="formatPrice(item.oldPrice)"></span>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <button
                                @click="addToCart(item.id)"
                                :disabled="adding === item.id"
                                class="flex-1 btn-primary py-2 rounded-xl text-xs font-medium flex items-center justify-center gap-1.5 disabled:opacity-50"
                            >
                                <template x-if="adding !== item.id">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        В корзину
                                    </span>
                                </template>
                                <template x-if="adding === item.id">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                </template>
                            </button>
                            <button
                                @click="removeItem(item.id)"
                                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors shrink-0"
                                title="Удалить из избранного"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function wishlistPage() {
        return {
            items: [],
            adding: null,

            loadItems() {
                if (typeof Alpine !== 'undefined' && Alpine.store('wishlist')) {
                    this.items = [...Alpine.store('wishlist').items];
                }
                window.addEventListener('wishlist-updated', () => {
                    if (Alpine.store('wishlist')) {
                        this.items = [...Alpine.store('wishlist').items];
                    }
                });
            },

            formatPrice(val) {
                if (!val) return '';
                return new Intl.NumberFormat('ru-RU').format(Math.round(val)) + ' {{ $currency }}';
            },

            removeItem(productId) {
                if (Alpine.store('wishlist')) {
                    Alpine.store('wishlist').remove(productId);
                    this.items = [...Alpine.store('wishlist').items];
                }
            },

            async addToCart(productId) {
                this.adding = productId;
                try {
                    const slug = '{{ $store->slug }}';
                    const resp = await fetch(`/store/${slug}/api/cart/add`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ product_id: productId, quantity: 1 }),
                    });
                    if (resp.ok) {
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Товар добавлен в корзину', type: 'success' } }));
                    } else {
                        const data = await resp.json();
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Ошибка', type: 'error' } }));
                    }
                } catch {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Ошибка соединения', type: 'error' } }));
                } finally {
                    this.adding = null;
                }
            }
        }
    }
</script>
