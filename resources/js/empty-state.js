/**
 * Dynamic Empty State Manager
 *
 * Programmatically show/hide empty states in containers
 * Usage: emptyState.show(container, options)
 */

const icons = {
    inbox: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    chat: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    photo: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    shopping: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    tag: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    'wifi-off': 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3l8.293 8.293',
    error: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

class EmptyState {
    constructor() {
        this.activeStates = new WeakMap();
    }

    /**
     * Show empty state in a container
     * @param {HTMLElement|string} container - Container element or selector
     * @param {Object} options - Configuration options
     */
    show(container, options = {}) {
        const el = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!el) {
            console.error('EmptyState: Container not found', container);
            return;
        }

        const config = {
            icon: options.icon || 'inbox',
            title: options.title || 'Пусто',
            message: options.message || 'Здесь пока ничего нет',
            actionText: options.actionText || 'Добавить',
            action: options.action || null,
            compact: options.compact || false,
            replace: options.replace !== false, // Replace content by default
        };

        // Create empty state element
        const emptyStateEl = this.create(config);

        if (config.replace) {
            // Replace all content
            el.innerHTML = '';
            el.appendChild(emptyStateEl);
        } else {
            // Append to existing content
            el.appendChild(emptyStateEl);
        }

        // Store reference
        this.activeStates.set(el, emptyStateEl);

        return emptyStateEl;
    }

    /**
     * Hide empty state from container
     * @param {HTMLElement|string} container - Container element or selector
     */
    hide(container) {
        const el = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!el) return;

        const emptyStateEl = this.activeStates.get(el);
        if (emptyStateEl && emptyStateEl.parentNode) {
            emptyStateEl.remove();
            this.activeStates.delete(el);
        }
    }

    /**
     * Create empty state element
     * @param {Object} config - Configuration
     * @returns {HTMLElement}
     */
    create(config) {
        const wrapper = document.createElement('div');
        wrapper.className = `empty-state flex flex-col items-center justify-center text-center ${config.compact ? 'py-8' : 'py-16'} px-6`;
        wrapper.setAttribute('data-empty-state', 'true');

        const iconSize = config.compact ? 'w-16 h-16' : 'w-24 h-24';
        const iconPath = icons[config.icon] || icons.inbox;

        wrapper.innerHTML = `
            <div class="mb-6 ${iconSize} text-gray-300 dark:text-gray-600">
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="${iconPath}"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                ${config.title}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 max-w-sm">
                ${config.message}
            </p>
            ${config.action ? `
                <button
                    class="empty-state-action inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 active:bg-blue-800 transition-colors"
                    data-haptic="light">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>${config.actionText}</span>
                </button>
            ` : ''}
        `;

        // Attach action handler
        if (config.action) {
            const btn = wrapper.querySelector('.empty-state-action');
            btn.addEventListener('click', () => {
                if (typeof config.action === 'function') {
                    config.action();
                } else if (typeof config.action === 'string') {
                    window.location.href = config.action;
                }

                // Haptic feedback
                if (window.haptic) {
                    window.haptic.light();
                }
            });
        }

        return wrapper;
    }

    /**
     * Toggle empty state based on condition
     * @param {HTMLElement|string} container - Container element or selector
     * @param {boolean} isEmpty - Whether to show empty state
     * @param {Object} options - Configuration options
     */
    toggle(container, isEmpty, options = {}) {
        if (isEmpty) {
            this.show(container, options);
        } else {
            this.hide(container);
        }
    }
}

// Initialize global instance
const emptyState = new EmptyState();

// Alpine.js integration
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        // Alpine directive: x-empty-state
        Alpine.directive('empty-state', (el, { expression }, { evaluateLater, cleanup }) => {
            const getConfig = evaluateLater(expression);

            const update = () => {
                getConfig(config => {
                    if (config && config.show) {
                        emptyState.show(el, config);
                    } else {
                        emptyState.hide(el);
                    }
                });
            };

            // Initial check
            update();

            // Watch for changes
            const observer = new MutationObserver(() => {
                // Auto-show if container is empty and no manual override
                if (el.children.length === 0 && !el.dataset.emptyStateManual) {
                    update();
                }
            });

            observer.observe(el, { childList: true });

            cleanup(() => {
                observer.disconnect();
                emptyState.hide(el);
            });
        });

        // Alpine magic helper
        Alpine.magic('emptyState', () => emptyState);

        // Alpine store
        Alpine.store('emptyState', {
            show(container, options) {
                return emptyState.show(container, options);
            },
            hide(container) {
                return emptyState.hide(container);
            },
            toggle(container, isEmpty, options) {
                return emptyState.toggle(container, isEmpty, options);
            }
        });
    });
}

// Export
window.emptyState = emptyState;
export default emptyState;
