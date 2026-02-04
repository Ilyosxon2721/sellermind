/**
 * Toast Notification System
 *
 * Native-style toast notifications for success, error, warning, and info messages
 * Usage: window.toast.success('Message'), window.toast.error('Error'), etc.
 */

class ToastManager {
    constructor() {
        this.toasts = [];
        this.container = null;
        this.maxToasts = 3;
        this.defaultDuration = 3000;

        this.init();
    }

    init() {
        // Create container if it doesn't exist
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }
    }

    show(options = {}) {
        const toast = {
            id: Date.now() + Math.random(),
            type: options.type || 'info',
            message: options.message || '',
            title: options.title || '',
            duration: options.duration !== undefined ? options.duration : this.defaultDuration,
            action: options.action || null,
            dismissible: options.dismissible !== false,
            icon: options.icon || this.getDefaultIcon(options.type || 'info')
        };

        // Remove oldest toast if at max capacity
        if (this.toasts.length >= this.maxToasts) {
            this.dismiss(this.toasts[0].id);
        }

        this.toasts.push(toast);
        this.render(toast);

        // Auto dismiss
        if (toast.duration > 0) {
            setTimeout(() => {
                this.dismiss(toast.id);
            }, toast.duration);
        }

        // Haptic feedback
        if (window.haptic) {
            if (toast.type === 'error') {
                window.haptic.error();
            } else if (toast.type === 'success') {
                window.haptic.success();
            } else {
                window.haptic.light();
            }
        }

        return toast.id;
    }

    getDefaultIcon(type) {
        const icons = {
            success: 'M5 13l4 4L19 7',
            error: 'M6 18L18 6M6 6l12 12',
            warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };
        return icons[type] || icons.info;
    }

    render(toast) {
        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${toast.type}`;
        toastEl.dataset.toastId = toast.id;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'polite');

        const iconColors = {
            success: 'text-green-500',
            error: 'text-red-500',
            warning: 'text-yellow-500',
            info: 'text-blue-500'
        };

        const bgColors = {
            success: 'bg-green-50 border-green-200',
            error: 'bg-red-50 border-red-200',
            warning: 'bg-yellow-50 border-yellow-200',
            info: 'bg-blue-50 border-blue-200'
        };

        toastEl.innerHTML = `
            <div class="toast-content ${bgColors[toast.type] || bgColors.info}">
                <div class="toast-icon ${iconColors[toast.type] || iconColors.info}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${toast.icon}"></path>
                    </svg>
                </div>
                <div class="toast-body">
                    ${toast.title ? `<div class="toast-title">${toast.title}</div>` : ''}
                    <div class="toast-message">${toast.message}</div>
                </div>
                ${toast.action ? `
                    <button class="toast-action" data-action="${toast.id}">
                        ${toast.action.label}
                    </button>
                ` : ''}
                ${toast.dismissible ? `
                    <button class="toast-close" data-close="${toast.id}" aria-label="Закрыть">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                ` : ''}
            </div>
        `;

        // Add to container
        this.container.appendChild(toastEl);

        // Trigger animation
        setTimeout(() => {
            toastEl.classList.add('toast-show');
        }, 10);

        // Attach event listeners
        if (toast.action) {
            const actionBtn = toastEl.querySelector('[data-action]');
            actionBtn.onclick = () => {
                if (typeof toast.action.handler === 'function') {
                    toast.action.handler();
                }
                this.dismiss(toast.id);
            };
        }

        if (toast.dismissible) {
            const closeBtn = toastEl.querySelector('[data-close]');
            closeBtn.onclick = () => {
                this.dismiss(toast.id);
            };
        }

        // Swipe to dismiss (mobile)
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        toastEl.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
            toastEl.style.transition = 'none';
        });

        toastEl.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
            const diff = currentX - startX;

            // Only allow right swipe
            if (diff > 0) {
                toastEl.style.transform = `translateX(${diff}px)`;
                toastEl.style.opacity = Math.max(0, 1 - diff / 200);
            }
        });

        toastEl.addEventListener('touchend', () => {
            if (!isDragging) return;
            isDragging = false;

            const diff = currentX - startX;
            toastEl.style.transition = '';

            if (diff > 100) {
                // Dismiss
                this.dismiss(toast.id);
            } else {
                // Reset
                toastEl.style.transform = '';
                toastEl.style.opacity = '';
            }

            startX = 0;
            currentX = 0;
        });
    }

    dismiss(id) {
        const toastEl = this.container.querySelector(`[data-toast-id="${id}"]`);
        if (!toastEl) return;

        toastEl.classList.remove('toast-show');
        toastEl.classList.add('toast-hide');

        setTimeout(() => {
            toastEl.remove();
            this.toasts = this.toasts.filter(t => t.id !== id);
        }, 300);
    }

    success(message, options = {}) {
        return this.show({
            type: 'success',
            message,
            ...options
        });
    }

    error(message, options = {}) {
        return this.show({
            type: 'error',
            message,
            duration: 5000, // Errors stay longer
            ...options
        });
    }

    warning(message, options = {}) {
        return this.show({
            type: 'warning',
            message,
            ...options
        });
    }

    info(message, options = {}) {
        return this.show({
            type: 'info',
            message,
            ...options
        });
    }

    loading(message, options = {}) {
        return this.show({
            type: 'info',
            message,
            duration: 0, // Don't auto-dismiss
            dismissible: false,
            icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            ...options
        });
    }

    dismissAll() {
        this.toasts.forEach(toast => {
            this.dismiss(toast.id);
        });
    }
}

// Initialize global toast manager
const toast = new ToastManager();

// Alpine.js integration
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        // Alpine magic helper
        Alpine.magic('toast', () => toast);

        // Alpine store
        Alpine.store('toast', {
            show(options) {
                return toast.show(options);
            },
            success(message, options) {
                return toast.success(message, options);
            },
            error(message, options) {
                return toast.error(message, options);
            },
            warning(message, options) {
                return toast.warning(message, options);
            },
            info(message, options) {
                return toast.info(message, options);
            },
            loading(message, options) {
                return toast.loading(message, options);
            },
            dismiss(id) {
                return toast.dismiss(id);
            }
        });
    });
}

// Export
window.toast = toast;
export default toast;

// Add CSS
const style = document.createElement('style');
style.textContent = `
    /* Toast Container */
    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        pointer-events: none;
        max-width: calc(100vw - 2rem);
    }

    @media (max-width: 640px) {
        .toast-container {
            top: auto;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 5rem);
            left: 1rem;
            right: 1rem;
        }
    }

    /* Toast Base */
    .toast {
        pointer-events: auto;
        transform: translateX(calc(100% + 1rem));
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform, opacity;
    }

    @media (max-width: 640px) {
        .toast {
            transform: translateY(calc(100% + 1rem));
        }
    }

    .toast-show {
        transform: translateX(0) !important;
        opacity: 1 !important;
    }

    @media (max-width: 640px) {
        .toast-show {
            transform: translateY(0) !important;
        }
    }

    .toast-hide {
        transform: translateX(calc(100% + 1rem)) !important;
        opacity: 0 !important;
    }

    @media (max-width: 640px) {
        .toast-hide {
            transform: translateY(calc(100% + 1rem)) !important;
        }
    }

    /* Toast Content */
    .toast-content {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem;
        background: white;
        border: 1px solid;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
        min-width: 300px;
        max-width: 400px;
    }

    @media (max-width: 640px) {
        .toast-content {
            min-width: 0;
            max-width: 100%;
        }
    }

    .toast-icon {
        flex-shrink: 0;
        margin-top: 0.125rem;
    }

    .toast-body {
        flex: 1;
        min-width: 0;
    }

    .toast-title {
        font-weight: 600;
        font-size: 0.875rem;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .toast-message {
        font-size: 0.875rem;
        color: #4b5563;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .toast-action {
        flex-shrink: 0;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #2563eb;
        background: transparent;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        white-space: nowrap;
    }

    .toast-action:hover {
        background-color: rgba(37, 99, 235, 0.1);
    }

    .toast-action:active {
        background-color: rgba(37, 99, 235, 0.2);
    }

    .toast-close {
        flex-shrink: 0;
        padding: 0.25rem;
        color: #6b7280;
        background: transparent;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 0;
    }

    .toast-close:hover {
        color: #111827;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .toast-close:active {
        background-color: rgba(0, 0, 0, 0.1);
    }

    /* Success */
    .toast-success .toast-title {
        color: #065f46;
    }

    .toast-success .toast-message {
        color: #047857;
    }

    /* Error */
    .toast-error .toast-title {
        color: #991b1b;
    }

    .toast-error .toast-message {
        color: #dc2626;
    }

    /* Warning */
    .toast-warning .toast-title {
        color: #92400e;
    }

    .toast-warning .toast-message {
        color: #d97706;
    }

    /* Info */
    .toast-info .toast-title {
        color: #1e40af;
    }

    .toast-info .toast-message {
        color: #2563eb;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .toast-content {
            background: #1f2937;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toast-title {
            color: #f9fafb;
        }

        .toast-message {
            color: #d1d5db;
        }

        .toast-close {
            color: #9ca3af;
        }

        .toast-close:hover {
            color: #f9fafb;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .bg-green-50 {
            background: rgba(16, 185, 129, 0.1) !important;
        }

        .bg-red-50 {
            background: rgba(239, 68, 68, 0.1) !important;
        }

        .bg-yellow-50 {
            background: rgba(245, 158, 11, 0.1) !important;
        }

        .bg-blue-50 {
            background: rgba(59, 130, 246, 0.1) !important;
        }

        .border-green-200 {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }

        .border-red-200 {
            border-color: rgba(239, 68, 68, 0.3) !important;
        }

        .border-yellow-200 {
            border-color: rgba(245, 158, 11, 0.3) !important;
        }

        .border-blue-200 {
            border-color: rgba(59, 130, 246, 0.3) !important;
        }

        .toast-success .toast-message {
            color: #34d399;
        }

        .toast-error .toast-message {
            color: #f87171;
        }

        .toast-warning .toast-message {
            color: #fbbf24;
        }

        .toast-info .toast-message {
            color: #60a5fa;
        }
    }

    /* Reduce motion */
    @media (prefers-reduced-motion: reduce) {
        .toast {
            transition: none !important;
        }
    }

    /* Loading spinner animation */
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .toast [d*="M4 4v5h.582"] {
        animation: spin 1s linear infinite;
        transform-origin: center;
    }
`;
document.head.appendChild(style);
