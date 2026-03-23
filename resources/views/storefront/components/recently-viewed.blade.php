{{-- Recently Viewed — недавно просмотренные товары --}}
@php $currency = $store->currency ?? 'сум'; @endphp

<section
    x-data="recentlyViewed()"
    x-on:track-product-view.window="trackView($event.detail)"
    x-show="visibleItems.length > 0"
    x-cloak
    class="py-12 bg-gray-50/50"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold">Недавно просмотренные</h2>
            <span class="text-sm text-gray-400" x-text="visibleItems.length + ' {{ trans_choice("товар|товара|товаров", 1) }}'"></span>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide" style="-webkit-overflow-scrolling: touch;">
            <template x-for="item in visibleItems" :key="item.id">
                <a :href="item.url" class="shrink-0 w-40 sm:w-48 snap-start group">
                    <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
                        <div class="aspect-square bg-gray-100 overflow-hidden">
                            <img
                                x-show="item.image"
                                :src="item.image"
                                :alt="item.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            >
                            <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-gray-600 transition-colors" x-text="item.name"></h3>
                            <p class="mt-1.5 text-sm font-bold" style="color: var(--primary);" x-text="formatPrice(item.price)"></p>
                        </div>
                    </div>
                </a>
            </template>
        </div>
    </div>
</section>

<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script nonce="{{ $cspNonce ?? '' }}">
    function recentlyViewed() {
        const excludeId = {{ $excludeProductId ?? 'null' }};
        const storageKey = 'recently_viewed_{{ $store->slug }}';
        const MAX_ITEMS = 20;

        return {
            items: [],

            get visibleItems() {
                return excludeId
                    ? this.items.filter(i => i.id !== excludeId)
                    : this.items;
            },

            init() {
                this.load();
            },

            load() {
                try {
                    const data = localStorage.getItem(storageKey);
                    this.items = data ? JSON.parse(data) : [];
                } catch {
                    this.items = [];
                }
            },

            save() {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(this.items));
                } catch {}
            },

            trackView(product) {
                if (!product || !product.id) return;

                // Удаляем если уже есть (перемещаем в начало)
                this.items = this.items.filter(i => i.id !== product.id);

                // Добавляем в начало
                this.items.unshift({
                    id: product.id,
                    name: product.name || '',
                    price: product.price || 0,
                    image: product.image || null,
                    url: product.url || '',
                });

                // Ограничиваем количество
                if (this.items.length > MAX_ITEMS) {
                    this.items = this.items.slice(0, MAX_ITEMS);
                }

                this.save();
            },

            formatPrice(val) {
                if (!val) return '';
                return new Intl.NumberFormat('ru-RU').format(Math.round(val)) + ' {{ $currency }}';
            }
        }
    }
</script>
