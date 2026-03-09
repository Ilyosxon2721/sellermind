/**
 * SellerMind PWA - Push Notifications Client
 * Клиент для управления Push уведомлениями
 */

class SmPush {
    constructor() {
        this.registration = null;
        this.subscription = null;
        this.vapidPublicKey = null;
    }

    /**
     * Инициализация модуля с Service Worker registration
     * @param {ServiceWorkerRegistration} registration - SW registration
     */
    async init(registration) {
        this.registration = registration;

        // Загрузить текущую подписку, если есть
        try {
            this.subscription = await this.registration.pushManager.getSubscription();
        } catch (e) {
            console.warn('SmPush: Не удалось получить текущую подписку', e);
        }
    }

    /**
     * Проверка поддержки Push API
     * @returns {boolean}
     */
    isSupported() {
        return 'PushManager' in window && 'serviceWorker' in navigator;
    }

    /**
     * Проверка, есть ли активная подписка
     * @returns {boolean}
     */
    isSubscribed() {
        return this.subscription !== null;
    }

    /**
     * Получить VAPID public key с сервера
     * @returns {Promise<string>}
     */
    async fetchVapidKey() {
        if (this.vapidPublicKey) {
            return this.vapidPublicKey;
        }

        try {
            const response = await fetch('/api/push/vapid-key', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Не удалось получить VAPID ключ');
            }

            const data = await response.json();
            this.vapidPublicKey = data.public_key;
            return this.vapidPublicKey;
        } catch (e) {
            console.error('SmPush: Ошибка получения VAPID ключа', e);
            throw e;
        }
    }

    /**
     * Конвертация Base64 VAPID ключа в Uint8Array
     * @param {string} base64String - Base64 строка
     * @returns {Uint8Array}
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Подписаться на Push уведомления
     * @returns {Promise<PushSubscription>}
     */
    async subscribe() {
        if (!this.isSupported()) {
            throw new Error('Push уведомления не поддерживаются');
        }

        if (!this.registration) {
            // Попытка получить registration
            this.registration = await navigator.serviceWorker.ready;
        }

        // Запрос разрешения на уведомления
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Разрешение на уведомления не получено');
        }

        // Получить VAPID ключ
        const vapidKey = await this.fetchVapidKey();
        const applicationServerKey = this.urlBase64ToUint8Array(vapidKey);

        // Создать подписку
        try {
            this.subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // Отправить подписку на сервер
            await this.saveSubscriptionToServer(this.subscription);

            // Сохранить флаг в localStorage
            localStorage.setItem('sm_push_enabled', 'true');

            return this.subscription;
        } catch (e) {
            console.error('SmPush: Ошибка подписки', e);
            throw e;
        }
    }

    /**
     * Отписаться от Push уведомлений
     * @returns {Promise<boolean>}
     */
    async unsubscribe() {
        if (!this.subscription) {
            localStorage.removeItem('sm_push_enabled');
            return true;
        }

        try {
            // Удалить подписку с сервера
            await this.removeSubscriptionFromServer(this.subscription);

            // Отписаться локально
            const result = await this.subscription.unsubscribe();
            this.subscription = null;

            // Очистить localStorage
            localStorage.removeItem('sm_push_enabled');

            return result;
        } catch (e) {
            console.error('SmPush: Ошибка отписки', e);
            throw e;
        }
    }

    /**
     * Получить текущую подписку
     * @returns {PushSubscription|null}
     */
    getSubscription() {
        return this.subscription;
    }

    /**
     * Отправить подписку на сервер
     * @param {PushSubscription} subscription
     * @returns {Promise<void>}
     */
    async saveSubscriptionToServer(subscription) {
        const subscriptionJson = subscription.toJSON();

        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: subscriptionJson.endpoint,
                keys: {
                    p256dh: subscriptionJson.keys?.p256dh || '',
                    auth: subscriptionJson.keys?.auth || ''
                }
            })
        });

        if (!response.ok) {
            throw new Error('Не удалось сохранить подписку на сервере');
        }
    }

    /**
     * Удалить подписку с сервера
     * @param {PushSubscription} subscription
     * @returns {Promise<void>}
     */
    async removeSubscriptionFromServer(subscription) {
        const response = await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: subscription.endpoint
            })
        });

        if (!response.ok) {
            console.warn('SmPush: Предупреждение при удалении подписки с сервера');
        }
    }

    /**
     * Синхронизировать состояние с сервером
     * Проверяет, что локальная подписка совпадает с серверной
     * @returns {Promise<void>}
     */
    async sync() {
        if (!this.registration) {
            this.registration = await navigator.serviceWorker.ready;
        }

        const currentSubscription = await this.registration.pushManager.getSubscription();

        if (currentSubscription && !this.isServerSubscriptionValid(currentSubscription)) {
            // Переподписаться, если подписка устарела
            await currentSubscription.unsubscribe();
            await this.subscribe();
        }

        this.subscription = currentSubscription;
    }

    /**
     * Проверить валидность подписки на сервере (заглушка)
     * @param {PushSubscription} subscription
     * @returns {boolean}
     */
    isServerSubscriptionValid(subscription) {
        // В реальном приложении здесь можно проверить на сервере
        return true;
    }
}

// Глобальный экземпляр
window.SmPush = new SmPush();

// Автоинициализация при готовности Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then(registration => {
        window.SmPush.init(registration);
    }).catch(e => {
        console.warn('SmPush: Service Worker не готов', e);
    });
}

export default window.SmPush;
