/**
 * Polling Manager
 * 
 * HTTP polling для real-time обновлений данных
 * Замена WebSocket (Reverb) для compatibility  с cPanel shared hosting
 * 
 * @author SellerMind AI
 * @version 1.0.0
 */

class PollingManager {
    constructor() {
        this.intervals = new Map();
        this.callbacks = new Map();
        this.lastCheckTimes = new Map();
        this.isActive = true;
        this.config = window.pollingConfig || {};
    }

    /**
     * Начать polling для endpoint
     * 
     * @param {string} key - Уникальный ключ для этого polling
     * @param {string} endpoint - API endpoint URL
     * @param {function} callback - Callback function (data) => {}
     * @param {number} interval - Интервал в миллисекундах (default: 15000)
     */
    start(key, endpoint, callback, interval = 15000) {
        if (this.intervals.has(key)) {
            console.warn(`Polling для "${key}" уже запущен. Останавливаем предыдущий.`);
            this.stop(key);
        }

        this.callbacks.set(key, callback);
        this.lastCheckTimes.set(key, new Date().toISOString());

        const poll = async () => {
            if (!this.isActive) {
                    return;
            }

            try {
                // Добавляем параметр last_check для оптимизации
                const separator = endpoint.includes('?') ? '&' : '?';
                const urlWithParams = `${endpoint}${separator}last_check=${this.lastCheckTimes.get(key)}`;

                const response = await fetch(urlWithParams, {
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include' // Для поддержки session cookies
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        console.error(`Polling ${key}: Unauthorized. Останавливаем polling.`);
                        this.stop(key);
                        // Опционально: redirect на login
                        // window.location.href = '/login';
                        return;
                    }
                    
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                
                // Обновляем время последней проверки
                if (data.timestamp) {
                    this.lastCheckTimes.set(key, data.timestamp);
                }

                // Вызываем callback только если есть данные
                if (callback && typeof callback === 'function') {
                    callback(data);
                }

            } catch (error) {
                console.error(`Polling error для "${key}":`, error);
                
                // Не останавливаем polling при network errors
                // Они могут быть временными
            }
        };

        // Первый запрос сразу
        poll();

        // Запускаем интервал
        const intervalId = setInterval(poll, interval);
        this.intervals.set(key, intervalId);

    }

    /**
     * Остановить конкретный polling
     * 
     * @param {string} key - Ключ polling для остановки
     */
    stop(key) {
        if (this.intervals.has(key)) {
            clearInterval(this.intervals.get(key));
            this.intervals.delete(key);
            this.callbacks.delete(key);
            this.lastCheckTimes.delete(key);
        } else {
            console.warn(`Polling "${key}" не найден`);
        }
    }

    /**
     * Остановить все активные polling
     */
    stopAll() {
        this.intervals.forEach((intervalId, key) => {
            clearInterval(intervalId);
        });
        this.intervals.clear();
        this.callbacks.clear();
        this.lastCheckTimes.clear();
    }

    /**
     * Приостановить все polling (при скрытии вкладки)
     */
    pause() {
        this.isActive = false;
    }

    /**
     * Возобновить все polling
     */
    resume() {
        this.isActive = true;

        // Сразу делаем poll для всех активных endpoints
        this.intervals.forEach((_, key) => {
            const callback = this.callbacks.get(key);
            if (callback) {
                // Trigger immediate poll после resume
                setTimeout(() => {
                }, 100);
            }
        });
    }

    /**
     * Получить статистику активных polling
     * 
     * @returns {object} Статистика
     */
    getStats() {
        return {
            active_count: this.intervals.size,
            is_active: this.isActive,
            keys: Array.from(this.intervals.keys()),
        };
    }

    /**
     * Проверить, запущен ли polling с конкретным ключом
     * 
     * @param {string} key - Ключ для проверки
     * @returns {boolean}
     */
    isRunning(key) {
        return this.intervals.has(key);
    }

    /**
     * Получить auth token
     * 
     * @private
     * @returns {string} Auth token
     */
    getToken() {
        // Попробовать получить из localStorage
        const localToken = localStorage.getItem('auth_token');
        if (localToken) {
            return localToken;
        }

        // Попробовать получить из мета-тега (для web session)
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.content;
        }

        // Попробовать получить из глобальной переменной
        if (window.authToken) {
            return window.authToken;
        }

        console.warn('Auth token не найден. Polling может не работать');
        return '';
    }
}

// Создать глобальный экземпляр
window.pollingManager = new PollingManager();

// Автоматически приостанавливать/возобновлять при скрытии/показе вкладки
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        window.pollingManager.pause();
    } else {
        window.pollingManager.resume();
    }
});

// Останавливать все polling при выходе со страницы
window.addEventListener('beforeunload', () => {
    window.pollingManager.stopAll();
});

// Debug: показывать статистику в консоли (только в development)
if (import.meta.env.DEV) {
    window.pollingStats = () => {
        const stats = window.pollingManager.getStats();
        console.table(stats);
        return stats;
    };
}

export default PollingManager;
