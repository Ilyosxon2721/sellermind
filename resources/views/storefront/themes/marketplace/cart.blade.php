@extends('storefront.layouts.app')

@section('page_title', 'Корзина — ' . $store->name)

@section('content')
@php $slug = $store->slug; $currency = $store->currency ?? 'сум'; @endphp

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-8" x-data="mpCart('{{ $slug }}', {{ json_encode(array_values($cart)) }})">

    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Корзина</h1>

    <template x-if="items.length === 0">
        <div class="text-center py-16">
            <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Корзина пуста</h2>
            <p class="text-gray-500 mb-6">Добавьте товары из каталога</p>
            <a href="/store/{{ $slug }}/catalog" class="inline-block px-6 py-3 rounded-xl text-white font-semibold" style="background: var(--primary);">Перейти в каталог</a>
        </div>
    </template>

    <template x-if="items.length > 0">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Список товаров --}}
            <div class="lg:col-span-2 space-y-3">
                <template x-for="(item, index) in items" :key="item.product_id + '_' + (item.variant_id || '')">
                    <div class="flex gap-4 bg-white rounded-xl border border-gray-100 p-4 hover:border-gray-200 transition">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-lg bg-gray-50 overflow-hidden flex-shrink-0">
                            <img :src="item.image || '/img/placeholder.png'" :alt="item.name" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2" x-text="item.name"></h3>
                            <p class="mt-1 text-base font-bold text-gray-900" x-text="formatPrice(item.price) + ' {{ $currency }}'"></p>
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                    <button @click="updateQty(item, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="w-10 text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button @click="updateQty(item, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                                <button @click="removeItem(item)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Итого --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-100 p-5 sticky top-24 space-y-4">
                    <h3 class="text-base font-bold text-gray-900">Ваш заказ</h3>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Товаров: <span x-text="totalCount"></span></span>
                        <span class="font-semibold text-gray-900" x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-3 border-t border-gray-100">
                        <span>Итого</span>
                        <span x-text="formatPrice(subtotal) + ' {{ $currency }}'"></span>
                    </div>
                    <a href="/store/{{ $slug }}/checkout" class="block w-full py-3 rounded-xl text-white text-center font-semibold hover:opacity-90 transition" style="background: var(--primary);">Оформить заказ</a>
                    <button @click="clearCart()" class="w-full text-sm text-gray-400 hover:text-red-500 transition-colors">Очистить корзину</button>
                </div>
            </div>
        </div>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function mpCart(slug, initialItems) {
    return {
        items: initialItems,
        get subtotal() { return this.items.reduce((s, i) => s + i.price * i.quantity, 0); },
        get totalCount() { return this.items.reduce((s, i) => s + i.quantity, 0); },

        async updateQty(item, newQty) {
            if (newQty < 1) return this.removeItem(item);
            item.quantity = newQty;
            await fetch(`/store/${slug}/api/cart/update`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: item.product_id, quantity: newQty })
            });
        },

        async removeItem(item) {
            this.items = this.items.filter(i => i !== item);
            await fetch(`/store/${slug}/api/cart/remove`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: item.product_id })
            });
        },

        async clearCart() {
            this.items = [];
            await fetch(`/store/${slug}/api/cart/clear`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }
            });
        }
    }
}
</script>
@endsection
