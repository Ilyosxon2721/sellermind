/**
 * Swipe Actions Component
 *
 * iOS/Android-style swipe gestures for list items
 * Swipe left/right to reveal actions
 */

class SwipeActions {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            threshold: 80, // Minimum swipe distance to trigger action
            maxSwipe: 200, // Maximum swipe distance
            leftActions: options.leftActions || [],
            rightActions: options.rightActions || [],
            enabled: true,
            ...options
        };

        this.touchStartX = 0;
        this.touchCurrentX = 0;
        this.isSwiping = false;
        this.swipeDistance = 0;
        this.actionsRevealed = null; // 'left' or 'right'

        this.createActionElements();
        this.attachListeners();
    }

    createActionElements() {
        // Wrap content
        const content = this.element.innerHTML;
        this.element.innerHTML = '';
        this.element.classList.add('swipe-actions-container');

        // Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'swipe-actions-wrapper';

        // Create content
        this.contentEl = document.createElement('div');
        this.contentEl.className = 'swipe-actions-content';
        this.contentEl.innerHTML = content;

        // Create left actions
        if (this.options.leftActions.length > 0) {
            this.leftActionsEl = this.createActionsElement(this.options.leftActions, 'left');
            this.wrapper.appendChild(this.leftActionsEl);
        }

        // Create right actions
        if (this.options.rightActions.length > 0) {
            this.rightActionsEl = this.createActionsElement(this.options.rightActions, 'right');
        }

        // Assemble
        this.wrapper.appendChild(this.contentEl);
        if (this.rightActionsEl) {
            this.wrapper.appendChild(this.rightActionsEl);
        }
        this.element.appendChild(this.wrapper);
    }

    createActionsElement(actions, side) {
        const container = document.createElement('div');
        container.className = `swipe-actions-${side}`;

        actions.forEach((action) => {
            const btn = document.createElement('button');
            btn.className = `swipe-action ${action.destructive ? 'swipe-action-destructive' : ''}`;
            btn.style.backgroundColor = action.color || (action.destructive ? '#ef4444' : '#3b82f6');
            btn.dataset.actionId = action.id || action.label;

            if (action.icon) {
                btn.innerHTML = `
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${action.icon}"></path>
                    </svg>
                    <span class="text-xs mt-1">${action.label}</span>
                `;
            } else {
                btn.innerHTML = `<span>${action.label}</span>`;
            }

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleAction(action);
            });

            container.appendChild(btn);
        });

        return container;
    }

    attachListeners() {
        this.contentEl.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: true });
        this.contentEl.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
        this.contentEl.addEventListener('touchend', this.onTouchEnd.bind(this), { passive: true });

        // Click outside to close
        document.addEventListener('click', (e) => {
            if (this.actionsRevealed && !this.element.contains(e.target)) {
                this.reset();
            }
        });
    }

    onTouchStart(e) {
        if (!this.options.enabled) return;

        this.touchStartX = e.touches[0].clientX;
        this.isSwiping = false;
        this.contentEl.style.transition = 'none';
    }

    onTouchMove(e) {
        if (!this.options.enabled) return;

        this.touchCurrentX = e.touches[0].clientX;
        const diff = this.touchCurrentX - this.touchStartX;

        // Only start swiping if horizontal movement is significant
        if (Math.abs(diff) > 10) {
            this.isSwiping = true;
            e.preventDefault();

            // Calculate swipe distance with resistance
            const resistance = 3;
            this.swipeDistance = diff / resistance;

            // Limit swipe distance
            const maxDistance = this.options.maxSwipe / resistance;
            this.swipeDistance = Math.max(-maxDistance, Math.min(maxDistance, this.swipeDistance));

            // Only allow swiping in the direction that has actions
            if (this.swipeDistance > 0 && !this.leftActionsEl) {
                this.swipeDistance = 0;
            }
            if (this.swipeDistance < 0 && !this.rightActionsEl) {
                this.swipeDistance = 0;
            }

            // Update visual position
            this.contentEl.style.transform = `translateX(${this.swipeDistance}px)`;

            // Update action visibility
            if (this.swipeDistance > 0 && this.leftActionsEl) {
                this.leftActionsEl.style.opacity = Math.min(1, Math.abs(this.swipeDistance) / 40);
            }
            if (this.swipeDistance < 0 && this.rightActionsEl) {
                this.rightActionsEl.style.opacity = Math.min(1, Math.abs(this.swipeDistance) / 40);
            }
        }
    }

    onTouchEnd() {
        if (!this.isSwiping) {
            this.reset();
            return;
        }

        this.contentEl.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

        const threshold = this.options.threshold / 3; // Adjust for resistance

        // Reveal actions if threshold is met
        if (Math.abs(this.swipeDistance) >= threshold) {
            if (this.swipeDistance > 0 && this.leftActionsEl) {
                // Reveal left actions
                this.revealActions('left');
            } else if (this.swipeDistance < 0 && this.rightActionsEl) {
                // Reveal right actions
                this.revealActions('right');
            } else {
                this.reset();
            }
        } else {
            this.reset();
        }

        this.isSwiping = false;
    }

    revealActions(side) {
        const distance = side === 'left' ? 80 : -80;
        this.contentEl.style.transform = `translateX(${distance}px)`;
        this.actionsRevealed = side;

        // Show actions with full opacity
        if (side === 'left' && this.leftActionsEl) {
            this.leftActionsEl.style.opacity = '1';
        }
        if (side === 'right' && this.rightActionsEl) {
            this.rightActionsEl.style.opacity = '1';
        }

        // Haptic feedback
        if (window.haptic) {
            window.haptic.light();
        }
    }

    reset() {
        this.contentEl.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        this.contentEl.style.transform = 'translateX(0)';
        this.swipeDistance = 0;
        this.actionsRevealed = null;

        // Hide actions
        if (this.leftActionsEl) {
            this.leftActionsEl.style.opacity = '0';
        }
        if (this.rightActionsEl) {
            this.rightActionsEl.style.opacity = '0';
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
            action.handler(this.element);
        }

        // Reset swipe
        this.reset();
    }

    enable() {
        this.options.enabled = true;
    }

    disable() {
        this.options.enabled = false;
        this.reset();
    }

    destroy() {
        this.reset();
        this.element.removeEventListener('touchstart', this.onTouchStart);
        this.element.removeEventListener('touchmove', this.onTouchMove);
        this.element.removeEventListener('touchend', this.onTouchEnd);
    }
}

// Alpine.js integration
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        // Alpine directive: x-swipe-actions
        Alpine.directive('swipe-actions', (el, { expression }, { evaluate, cleanup }) => {
            const options = expression ? evaluate(expression) : {};

            // Initialize swipe actions
            const swipeActions = new SwipeActions(el, options);

            // Cleanup on element removal
            cleanup(() => {
                swipeActions.destroy();
            });
        });

        // Alpine magic helper
        Alpine.magic('swipeActions', () => {
            return {
                create(element, options) {
                    return new SwipeActions(element, options);
                }
            };
        });
    });
}

// Export
window.SwipeActions = SwipeActions;
export default SwipeActions;

// Add CSS
const style = document.createElement('style');
style.textContent = `
    /* Swipe Actions Container */
    .swipe-actions-container {
        position: relative;
        overflow: hidden;
        touch-action: pan-y;
    }

    .swipe-actions-wrapper {
        position: relative;
        display: flex;
        align-items: stretch;
    }

    .swipe-actions-content {
        position: relative;
        flex: 1;
        background: white;
        z-index: 2;
        width: 100%;
        will-change: transform;
    }

    .swipe-actions-left,
    .swipe-actions-right {
        position: absolute;
        top: 0;
        bottom: 0;
        display: flex;
        align-items: stretch;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .swipe-actions-left {
        left: 0;
        flex-direction: row;
    }

    .swipe-actions-right {
        right: 0;
        flex-direction: row-reverse;
    }

    .swipe-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 80px;
        padding: 0 1rem;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .swipe-action:active {
        opacity: 0.8;
    }

    .swipe-action-destructive {
        background-color: #ef4444;
    }

    .swipe-action svg {
        margin-bottom: 0.25rem;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .swipe-actions-content {
            background: #1f2937;
        }
    }

    /* Prevent text selection during swipe */
    .swipe-actions-container.swiping {
        user-select: none;
        -webkit-user-select: none;
    }
`;
document.head.appendChild(style);
