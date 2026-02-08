/**
 * SellerMind PWA - Haptic Feedback
 *
 * Тактильная обратная связь для нативного ощущения
 */

class SmHaptic {
    constructor() {
        this.supported = 'vibrate' in navigator;
    }

    /**
     * Лёгкая вибрация — нажатие кнопки, переключение
     */
    light() {
        if (this.supported) navigator.vibrate(10);
    }

    /**
     * Средняя вибрация — выбор элемента, свайп
     */
    medium() {
        if (this.supported) navigator.vibrate(20);
    }

    /**
     * Сильная вибрация — важное действие
     */
    heavy() {
        if (this.supported) navigator.vibrate(40);
    }

    /**
     * Успех — двойная короткая вибрация
     */
    success() {
        if (this.supported) navigator.vibrate([10, 50, 10]);
    }

    /**
     * Ошибка — тройная вибрация
     */
    error() {
        if (this.supported) navigator.vibrate([50, 100, 50]);
    }

    /**
     * Предупреждение
     */
    warning() {
        if (this.supported) navigator.vibrate([30, 60, 30]);
    }

    /**
     * Кастомный паттерн вибрации
     */
    custom(pattern) {
        if (this.supported) navigator.vibrate(pattern);
    }
}

window.SmHaptic = new SmHaptic();
