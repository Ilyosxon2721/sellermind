/**
 * Pull-to-Refresh for Native App Experience
 *
 * Works on mobile devices with touch events
 * Provides native iOS/Android-style pull-to-refresh
 */

class PullToRefresh {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            threshold: 80, // Distance to trigger refresh
            maxDistance: 120, // Maximum pull distance
            resistance: 2.5, // Pull resistance factor
            onRefresh: options.onRefresh || (() => Promise.resolve()),
            enabled: true,
            ...options
        };

        this.touchStartY = 0;
        this.touchCurrentY = 0;
        this.pulling = false;
        this.refreshing = false;
        this.distance = 0;

        this.createIndicator();
        this.attachListeners();
    }

    createIndicator() {
        // Create pull indicator
        this.indicator = document.createElement('div');
        this.indicator.className = 'pull-to-refresh-indicator';
        this.indicator.innerHTML = `
            <div class="pull-to-refresh-content">
                <div class="pull-to-refresh-spinner">
                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="pull-to-refresh-arrow">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
                <div class="pull-to-refresh-text text-sm font-medium text-gray-600"></div>
            </div>
        `;

        // Insert before element
        this.element.parentNode.insertBefore(this.indicator, this.element);

        this.spinner = this.indicator.querySelector('.pull-to-refresh-spinner');
        this.arrow = this.indicator.querySelector('.pull-to-refresh-arrow');
        this.text = this.indicator.querySelector('.pull-to-refresh-text');
    }

    attachListeners() {
        this.element.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
        this.element.addEventListener('touchend', this.onTouchEnd.bind(this), { passive: true });
    }

    onTouchStart(e) {
        if (!this.options.enabled || this.refreshing) return;

        // Only enable if at top of scrollable element
        if (this.element.scrollTop > 0) return;

        this.touchStartY = e.touches[0].clientY;
        this.pulling = false;
    }

    onTouchMove(e) {
        if (!this.options.enabled || this.refreshing) return;
        if (this.element.scrollTop > 0) return;

        this.touchCurrentY = e.touches[0].clientY;
        const diff = this.touchCurrentY - this.touchStartY;

        // Only pull down
        if (diff < 0) return;

        // Apply resistance
        this.distance = Math.min(diff / this.options.resistance, this.options.maxDistance);

        if (this.distance > 5) {
            this.pulling = true;
            e.preventDefault(); // Prevent scroll

            // Update indicator
            this.updateIndicator();
        }
    }

    async onTouchEnd() {
        if (!this.pulling || this.refreshing) {
            this.reset();
            return;
        }

        if (this.distance >= this.options.threshold) {
            // Trigger refresh
            await this.refresh();
        } else {
            // Not enough distance, reset
            this.reset();
        }
    }

    updateIndicator() {
        const progress = Math.min(this.distance / this.options.threshold, 1);
        const rotation = progress * 180;

        // Position indicator
        this.indicator.style.height = `${this.distance}px`;
        this.indicator.style.opacity = progress;

        // Rotate arrow
        this.arrow.style.transform = `rotate(${rotation}deg)`;

        // Update text
        if (this.distance >= this.options.threshold) {
            this.text.textContent = 'Отпустите для обновления';
            this.arrow.style.transform = 'rotate(180deg) scale(1.1)';
        } else {
            this.text.textContent = 'Потяните для обновления';
        }
    }

    async refresh() {
        this.refreshing = true;
        this.pulling = false;

        // Show spinner
        this.indicator.style.height = `${this.options.threshold}px`;
        this.arrow.style.display = 'none';
        this.spinner.style.display = 'block';
        this.text.textContent = 'Обновление...';

        // Haptic feedback
        if (window.haptic) {
            window.haptic.medium();
        }

        try {
            // Execute refresh callback
            await this.options.onRefresh();

            // Success haptic
            if (window.haptic) {
                window.haptic.success();
            }
        } catch (error) {
            console.error('Pull-to-refresh error:', error);

            // Error haptic
            if (window.haptic) {
                window.haptic.error();
            }
        } finally {
            // Reset after delay
            setTimeout(() => {
                this.reset();
                this.refreshing = false;
            }, 500);
        }
    }

    reset() {
        this.pulling = false;
        this.distance = 0;

        // Animate back
        this.indicator.style.transition = 'height 0.3s ease, opacity 0.3s ease';
        this.indicator.style.height = '0px';
        this.indicator.style.opacity = '0';

        // Reset arrow
        this.arrow.style.transform = 'rotate(0deg)';
        this.arrow.style.display = 'block';
        this.spinner.style.display = 'none';

        // Clear transition after animation
        setTimeout(() => {
            this.indicator.style.transition = '';
        }, 300);
    }

    enable() {
        this.options.enabled = true;
    }

    disable() {
        this.options.enabled = false;
    }

    destroy() {
        this.indicator.remove();
        this.element.removeEventListener('touchstart', this.onTouchStart);
        this.element.removeEventListener('touchmove', this.onTouchMove);
        this.element.removeEventListener('touchend', this.onTouchEnd);
    }
}

// Alpine.js directive
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        Alpine.directive('pull-to-refresh', (el, { expression }, { evaluate, cleanup }) => {
            // Get refresh function from expression
            const refreshFn = evaluate(expression);

            // Initialize pull-to-refresh
            const ptr = new PullToRefresh(el, {
                onRefresh: async () => {
                    if (typeof refreshFn === 'function') {
                        await refreshFn();
                    }
                }
            });

            // Cleanup on element removal
            cleanup(() => {
                ptr.destroy();
            });
        });
    });
}

// Export for module usage
export default PullToRefresh;

// Global access
window.PullToRefresh = PullToRefresh;

// Add CSS
const style = document.createElement('style');
style.textContent = `
    .pull-to-refresh-indicator {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 0;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        background: linear-gradient(to bottom, rgba(249, 250, 251, 0) 0%, rgba(249, 250, 251, 1) 100%);
        z-index: 10;
        opacity: 0;
        transition: none;
    }

    .pull-to-refresh-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-bottom: 1rem;
    }

    .pull-to-refresh-spinner,
    .pull-to-refresh-arrow {
        margin-bottom: 0.5rem;
    }

    .pull-to-refresh-spinner {
        display: none;
    }

    .pull-to-refresh-arrow {
        transition: transform 0.2s ease;
    }

    .pull-to-refresh-text {
        white-space: nowrap;
    }

    /* Make sure parent is relative */
    [x-data][x-pull-to-refresh],
    .pull-to-refresh-container {
        position: relative;
    }
`;
document.head.appendChild(style);
