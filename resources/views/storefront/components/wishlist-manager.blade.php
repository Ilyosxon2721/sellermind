{{-- Wishlist Manager — глобальный Alpine store для избранного --}}
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('alpine:init', () => {
        Alpine.store('wishlist', {
            items: [],
            _storageKey: 'wishlist_{{ $store->slug }}',

            init() {
                this.load();
            },

            load() {
                try {
                    const data = localStorage.getItem(this._storageKey);
                    this.items = data ? JSON.parse(data) : [];
                } catch {
                    this.items = [];
                }
            },

            save() {
                try {
                    localStorage.setItem(this._storageKey, JSON.stringify(this.items));
                } catch {}
                window.dispatchEvent(new CustomEvent('wishlist-updated'));
            },

            toggle(product) {
                if (this.has(product.id)) {
                    this.remove(product.id);
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Удалено из избранного', type: 'success' }
                    }));
                } else {
                    this.items.push({
                        id: product.id,
                        name: product.name || '',
                        price: product.price || 0,
                        oldPrice: product.oldPrice || null,
                        image: product.image || null,
                        url: product.url || '',
                    });
                    this.save();
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Добавлено в избранное', type: 'success' }
                    }));
                }
            },

            has(productId) {
                return this.items.some(item => item.id === productId);
            },

            remove(productId) {
                this.items = this.items.filter(item => item.id !== productId);
                this.save();
            },

            get count() {
                return this.items.length;
            }
        });
    });
</script>
