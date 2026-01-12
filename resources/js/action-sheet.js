/**
 * Action Sheet Component for Native-like Menus
 *
 * iOS/Android-style bottom sheet menus
 * Usage with Alpine.js: x-data="actionSheet()"
 */

// Alpine.js component
document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('actionSheet', () => ({
            isOpen: false,
            actions: [],
            title: '',
            message: '',
            cancelText: 'Отмена',

            open(options = {}) {
                this.title = options.title || '';
                this.message = options.message || '';
                this.actions = options.actions || [];
                this.cancelText = options.cancelText || 'Отмена';
                this.isOpen = true;

                // Haptic feedback
                if (window.haptic) {
                    window.haptic.light();
                }

                // Prevent body scroll
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.isOpen = false;

                // Restore body scroll
                document.body.style.overflow = '';

                // Clear data after animation
                setTimeout(() => {
                    this.actions = [];
                    this.title = '';
                    this.message = '';
                }, 300);
            },

            handleAction(action) {
                // Haptic feedback
                if (window.haptic) {
                    if (action.destructive) {
                        window.haptic.medium();
                    } else {
                        window.haptic.light();
                    }
                }

                // Execute action
                if (typeof action.handler === 'function') {
                    action.handler();
                }

                // Close sheet
                this.close();
            }
        }));

        // Global helper to show action sheets
        window.Alpine.magic('actionSheet', () => {
            return {
                show(options) {
                    // Dispatch event to open action sheet
                    window.dispatchEvent(new CustomEvent('action-sheet:open', {
                        detail: options
                    }));
                }
            };
        });
    }
});

// Standalone class for non-Alpine usage
class ActionSheet {
    constructor(options = {}) {
        this.container = null;
        this.backdrop = null;
        this.sheet = null;
        this.isOpen = false;

        this.options = {
            title: '',
            message: '',
            actions: [],
            cancelText: 'Отмена',
            ...options
        };

        this.create();
    }

    create() {
        // Create backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'action-sheet-backdrop';
        this.backdrop.onclick = () => this.close();

        // Create sheet
        this.sheet = document.createElement('div');
        this.sheet.className = 'action-sheet';
        this.sheet.innerHTML = this.renderContent();

        // Create container
        this.container = document.createElement('div');
        this.container.className = 'action-sheet-container';
        this.container.appendChild(this.backdrop);
        this.container.appendChild(this.sheet);

        // Attach event listeners
        this.attachListeners();
    }

    renderContent() {
        let html = `
            <div class="action-sheet-handle"></div>
        `;

        if (this.options.title || this.options.message) {
            html += `<div class="action-sheet-header">`;
            if (this.options.title) {
                html += `<h3 class="action-sheet-title">${this.options.title}</h3>`;
            }
            if (this.options.message) {
                html += `<p class="action-sheet-message">${this.options.message}</p>`;
            }
            html += `</div>`;
        }

        html += `<div class="action-sheet-actions">`;
        this.options.actions.forEach((action, index) => {
            const iconHtml = action.icon ? `
                <svg class="action-sheet-action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${action.icon}
                </svg>
            ` : '';

            const className = `action-sheet-action ${action.destructive ? 'action-sheet-action-destructive' : ''}`;
            html += `
                <button class="${className}" data-action-index="${index}">
                    ${iconHtml}
                    <span>${action.title}</span>
                </button>
            `;
        });
        html += `</div>`;

        html += `
            <div class="action-sheet-cancel">
                <button class="action-sheet-action action-sheet-action-cancel">
                    ${this.options.cancelText}
                </button>
            </div>
        `;

        return html;
    }

    attachListeners() {
        // Action buttons
        this.sheet.querySelectorAll('[data-action-index]').forEach(btn => {
            btn.onclick = () => {
                const index = parseInt(btn.dataset.actionIndex);
                this.handleAction(this.options.actions[index]);
            };
        });

        // Cancel button
        const cancelBtn = this.sheet.querySelector('.action-sheet-action-cancel');
        if (cancelBtn) {
            cancelBtn.onclick = () => this.close();
        }
    }

    handleAction(action) {
        // Haptic feedback
        if (window.haptic) {
            if (action.destructive) {
                window.haptic.medium();
            } else {
                window.haptic.light();
            }
        }

        // Execute handler
        if (typeof action.handler === 'function') {
            action.handler();
        }

        this.close();
    }

    open() {
        if (this.isOpen) return;

        document.body.appendChild(this.container);
        this.isOpen = true;

        // Trigger animation
        setTimeout(() => {
            this.backdrop.classList.add('active');
            this.sheet.classList.add('active');
        }, 10);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Haptic feedback
        if (window.haptic) {
            window.haptic.light();
        }
    }

    close() {
        if (!this.isOpen) return;

        this.backdrop.classList.remove('active');
        this.sheet.classList.remove('active');

        // Remove after animation
        setTimeout(() => {
            if (this.container && this.container.parentNode) {
                this.container.parentNode.removeChild(this.container);
            }
            this.isOpen = false;

            // Restore body scroll
            document.body.style.overflow = '';
        }, 300);
    }

    static show(options) {
        const sheet = new ActionSheet(options);
        sheet.open();
        return sheet;
    }
}

// Export
window.ActionSheet = ActionSheet;
export default ActionSheet;

// Add CSS
const style = document.createElement('style');
style.textContent = `
    /* Action Sheet Styles */
    .action-sheet-container {
        position: fixed;
        inset: 0;
        z-index: 100;
        pointer-events: none;
    }

    .action-sheet-container.active,
    .action-sheet-container > * {
        pointer-events: auto;
    }

    .action-sheet-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    .action-sheet-backdrop.active {
        opacity: 1;
    }

    .action-sheet {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 80vh;
        overflow-y: auto;
        padding-bottom: env(safe-area-inset-bottom, 0px);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    }

    .action-sheet.active {
        transform: translateY(0);
    }

    .action-sheet-handle {
        width: 36px;
        height: 4px;
        background: #d1d5db;
        border-radius: 2px;
        margin: 0.75rem auto;
    }

    .action-sheet-header {
        padding: 1rem 1.5rem;
        text-align: center;
        border-bottom: 1px solid #f3f4f6;
    }

    .action-sheet-title {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
        margin: 0 0 0.25rem 0;
    }

    .action-sheet-message {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
    }

    .action-sheet-actions {
        padding: 0.5rem 0;
    }

    .action-sheet-action {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 1rem 1.5rem;
        font-size: 1rem;
        color: #111827;
        background: transparent;
        border: none;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .action-sheet-action:active {
        background-color: #f9fafb;
    }

    .action-sheet-action-icon {
        width: 1.5rem;
        height: 1.5rem;
        flex-shrink: 0;
    }

    .action-sheet-action-destructive {
        color: #dc2626;
    }

    .action-sheet-cancel {
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: #f9fafb;
    }

    .action-sheet-action-cancel {
        width: 100%;
        padding: 1rem;
        font-size: 1rem;
        font-weight: 600;
        color: #2563eb;
        background: white;
        border: none;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .action-sheet-action-cancel:active {
        background-color: #f3f4f6;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .action-sheet {
            background: #1f2937;
        }

        .action-sheet-title {
            color: #f9fafb;
        }

        .action-sheet-message {
            color: #9ca3af;
        }

        .action-sheet-action {
            color: #f9fafb;
        }

        .action-sheet-action:active {
            background-color: #374151;
        }

        .action-sheet-cancel {
            background: #111827;
        }

        .action-sheet-action-cancel {
            background: #374151;
            color: #60a5fa;
        }

        .action-sheet-action-cancel:active {
            background-color: #4b5563;
        }
    }
`;
document.head.appendChild(style);

console.log('✅ Action Sheet: Loaded');
