// Service Worker для SellerMind PWA
const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `sellermind-${CACHE_VERSION}`;

// Ресурсы для кеширования при установке
const PRECACHE_URLS = [
    '/',
    '/dashboard',
    '/manifest.json',
    '/offline.html'
];

// Установка Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...', CACHE_VERSION);

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Precaching app shell');
            // Не критично если что-то не закешируется
            return cache.addAll(PRECACHE_URLS).catch((err) => {
                console.warn('[SW] Precaching failed for some resources:', err);
            });
        })
    );

    // Активировать новый SW сразу
    self.skipWaiting();
});

// Активация Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...', CACHE_VERSION);

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );

    // Взять под контроль все открытые страницы
    return self.clients.claim();
});

// Fetch стратегия: Network First, fallback to Cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Пропускаем Chrome extensions и не-HTTP запросы
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Пропускаем API запросы - они должны всегда быть свежими
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // Пропускаем WebSocket
    if (url.pathname.startsWith('/app/')) {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                // Клонируем ответ для кеша
                if (response.status === 200) {
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME).then((cache) => {
                        // Кешируем только GET запросы
                        if (request.method === 'GET') {
                            cache.put(request, responseToCache);
                        }
                    });
                }

                return response;
            })
            .catch((error) => {
                console.log('[SW] Fetch failed, trying cache:', request.url);

                return caches.match(request).then((response) => {
                    if (response) {
                        return response;
                    }

                    // Если это HTML страница и нет в кеше - показываем offline страницу
                    if (request.headers.get('accept').includes('text/html')) {
                        return caches.match('/offline.html');
                    }

                    return new Response('Offline - content not available', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({
                            'Content-Type': 'text/plain'
                        })
                    });
                });
            })
    );
});

// Обработка сообщений от клиента
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_VERSION });
    }
});

// Push уведомления (для будущего функционала)
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const title = data.title || 'SellerMind';
    const options = {
        body: data.body || '',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: data.data || {},
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Клик по уведомлению
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data.url || '/dashboard')
    );
});
