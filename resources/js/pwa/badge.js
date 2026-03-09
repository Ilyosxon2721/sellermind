/**
 * SellerMind PWA - Badge API
 * Управление числовым индикатором на иконке приложения
 */

class SmBadge {
    constructor() {
        this.supported = 'setAppBadge' in navigator;
        this.currentCount = 0;
    }

    /**
     * Установить количество на бейдже
     * @param {number} count - Число для отображения
     * @returns {Promise<boolean>} - Успешность операции
     */
    async set(count) {
        if (!this.supported) return false;

        try {
            this.currentCount = count;
            if (count > 0) {
                await navigator.setAppBadge(count);
            } else {
                await navigator.clearAppBadge();
            }
            return true;
        } catch (e) {
            console.warn('Badge API error:', e);
            return false;
        }
    }

    /**
     * Увеличить счетчик на 1
     * @returns {Promise<boolean>} - Успешность операции
     */
    async increment() {
        return this.set(this.currentCount + 1);
    }

    /**
     * Уменьшить счетчик на 1
     * @returns {Promise<boolean>} - Успешность операции
     */
    async decrement() {
        return this.set(Math.max(0, this.currentCount - 1));
    }

    /**
     * Очистить бейдж
     * @returns {Promise<boolean>} - Успешность операции
     */
    async clear() {
        return this.set(0);
    }

    /**
     * Получить текущее значение счетчика
     * @returns {number} - Текущий счетчик
     */
    getCount() {
        return this.currentCount;
    }

    /**
     * Проверка поддержки Badge API
     * @returns {boolean} - Поддерживается ли API
     */
    isSupported() {
        return this.supported;
    }
}

window.SmBadge = new SmBadge();

export default window.SmBadge;
