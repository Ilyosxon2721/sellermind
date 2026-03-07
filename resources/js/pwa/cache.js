/**
 * SellerMind PWA - IndexedDB Cache
 */

class SmCache {
    constructor() {
        this.dbName = 'sellermind_cache';
        this.dbVersion = 1;
        this.db = null;

        this.stores = {
            dashboard: { keyPath: 'id', ttl: 5 * 60 * 1000 },
            products: { keyPath: 'id', ttl: 30 * 60 * 1000 },
            orders: { keyPath: 'id', ttl: 5 * 60 * 1000 },
            balance: { keyPath: 'sku_id', ttl: 5 * 60 * 1000 },
            categories: { keyPath: 'id', ttl: 24 * 60 * 60 * 1000 },
            warehouses: { keyPath: 'id', ttl: 60 * 60 * 1000 },
        };
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                Object.keys(this.stores).forEach(storeName => {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, {
                            keyPath: this.stores[storeName].keyPath
                        });
                        store.createIndex('timestamp', 'timestamp', { unique: false });
                    }
                });
            };
        });
    }

    async get(storeName, key) {
        await this.ensureDb();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                const result = request.result;
                if (!result) {
                    resolve(null);
                    return;
                }

                const ttl = this.stores[storeName]?.ttl || 5 * 60 * 1000;
                if (Date.now() - result.timestamp > ttl) {
                    this.delete(storeName, key);
                    resolve(null);
                } else {
                    resolve(result.data);
                }
            };
        });
    }

    async set(storeName, key, data) {
        await this.ensureDb();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);

            const record = {
                [this.stores[storeName].keyPath]: key,
                data: data,
                timestamp: Date.now()
            };

            const request = store.put(record);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }

    async getAll(storeName) {
        await this.ensureDb();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                const ttl = this.stores[storeName]?.ttl || 5 * 60 * 1000;
                const now = Date.now();
                const results = request.result
                    .filter(item => now - item.timestamp <= ttl)
                    .map(item => item.data);
                resolve(results);
            };
        });
    }

    async delete(storeName, key) {
        await this.ensureDb();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }

    async clear(storeName) {
        await this.ensureDb();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }

    async ensureDb() {
        if (!this.db) {
            await this.init();
        }
    }
}

window.SmCache = new SmCache();
