/**
 * SellerMind PWA - Offline Support with Optimistic UI
 */

class SmOffline {
    constructor() {
        this.isOnline = navigator.onLine;
        this.listeners = [];

        window.addEventListener('online', () => this.setOnline(true));
        window.addEventListener('offline', () => this.setOnline(false));
    }

    setOnline(status) {
        this.isOnline = status;
        this.listeners.forEach(fn => fn(status));

        if (status) {
            this.showToast('Соединение восстановлено', 'success');
        } else {
            this.showToast('Нет соединения', 'warning');
        }
    }

    onStatusChange(callback) {
        this.listeners.push(callback);
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'sm-toast sm-toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('visible'), 10);
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Fetch с автоматическим кэшированием и fallback на кэш
     */
    async fetch(url, options = {}) {
        const cacheKey = options.cacheKey || url;
        const storeName = options.storeName || 'dashboard';

        let cachedData = null;
        try {
            cachedData = await window.SmCache.get(storeName, cacheKey);
        } catch (e) {}

        if (!this.isOnline) {
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw new Error('Нет соединения и нет кэша');
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...options.headers
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();

            try {
                await window.SmCache.set(storeName, cacheKey, data);
            } catch (e) {}

            return { data, fromCache: false };
        } catch (error) {
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw error;
        }
    }
}

window.SmOffline = new SmOffline();
