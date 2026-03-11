/**
 * SmartRefresh — умное обновление данных с анимацией
 *
 * Сравнивает старые и новые данные, определяет:
 * - Новые элементы → анимация вставки (sm-row-insert / sm-card-insert)
 * - Изменённые элементы → анимация подсветки (sm-row-update / sm-card-update)
 * - Удалённые элементы → анимация схлопывания (sm-row-remove)
 *
 * Использование в Alpine.js:
 *   const result = SmartRefresh.merge(this.orders, newOrders, 'id');
 *   this.orders = result.merged;
 *   // CSS классы применяются автоматически через _smAnim
 */
window.SmartRefresh = {
    /**
     * Сравнить и объединить массивы с маркировкой анимаций
     *
     * @param {Array} oldItems - текущие данные
     * @param {Array} newItems - новые данные с сервера
     * @param {string} key - поле для идентификации (например 'id')
     * @param {Object} options - настройки
     * @returns {{ merged: Array, stats: Object }}
     */
    merge(oldItems, newItems, key = 'id', options = {}) {
        const {
            // Какие поля сравнивать для определения изменений
            compareFields = null,
            // Длительность анимации в ms (для автоочистки)
            animDuration = 1200,
        } = options;

        if (!oldItems || oldItems.length === 0) {
            // Первая загрузка — не анимируем
            return { merged: newItems, stats: { added: 0, updated: 0, removed: 0 } };
        }

        const oldMap = new Map();
        oldItems.forEach(item => oldMap.set(String(item[key]), item));

        const newMap = new Map();
        newItems.forEach(item => newMap.set(String(item[key]), item));

        const merged = [];
        let added = 0, updated = 0, removed = 0;

        // Проходим по новым элементам
        newItems.forEach(newItem => {
            const id = String(newItem[key]);
            const oldItem = oldMap.get(id);

            if (!oldItem) {
                // Новый элемент — вставка
                newItem._smAnim = 'insert';
                newItem._smAnimAt = Date.now();
                added++;
            } else if (this._hasChanges(oldItem, newItem, compareFields)) {
                // Изменённый элемент — обновление
                newItem._smAnim = 'update';
                newItem._smAnimAt = Date.now();
                updated++;
            } else {
                // Без изменений — убираем старую анимацию если прошло время
                if (oldItem._smAnim && (Date.now() - (oldItem._smAnimAt || 0)) > animDuration) {
                    newItem._smAnim = null;
                } else {
                    newItem._smAnim = oldItem._smAnim;
                    newItem._smAnimAt = oldItem._smAnimAt;
                }
            }
            merged.push(newItem);
        });

        // Считаем удалённые (были в старых, нет в новых)
        oldItems.forEach(oldItem => {
            if (!newMap.has(String(oldItem[key]))) {
                removed++;
            }
        });

        return {
            merged,
            stats: { added, updated, removed }
        };
    },

    /**
     * Получить CSS-класс анимации для строки таблицы
     */
    rowClass(item) {
        if (!item || !item._smAnim) return '';
        if (item._smAnim === 'insert') return 'sm-row-insert';
        if (item._smAnim === 'update') return 'sm-row-update';
        return '';
    },

    /**
     * Получить CSS-класс анимации для карточки (мобильная версия)
     */
    cardClass(item) {
        if (!item || !item._smAnim) return '';
        if (item._smAnim === 'insert') return 'sm-card-insert';
        if (item._smAnim === 'update') return 'sm-card-update';
        return '';
    },

    /**
     * Получить CSS-класс для стат-карточки при изменении значения
     */
    statClass(oldValue, newValue) {
        if (oldValue !== newValue && oldValue !== undefined) {
            return 'sm-stat-update';
        }
        return '';
    },

    /**
     * Сравнение двух объектов
     */
    _hasChanges(oldItem, newItem, compareFields) {
        const fields = compareFields || Object.keys(newItem).filter(k => !k.startsWith('_sm'));

        for (const field of fields) {
            if (field.startsWith('_sm')) continue;
            const oldVal = oldItem[field];
            const newVal = newItem[field];
            // Сравниваем примитивы и простые значения
            if (typeof oldVal !== 'object' && typeof newVal !== 'object') {
                if (String(oldVal) !== String(newVal)) return true;
            }
        }
        return false;
    },

    /**
     * Очистить анимации у всех элементов (вызывать после завершения)
     */
    clearAnimations(items, delay = 1500) {
        setTimeout(() => {
            items.forEach(item => {
                item._smAnim = null;
                item._smAnimAt = null;
            });
        }, delay);
    }
};
