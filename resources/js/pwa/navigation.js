/**
 * SellerMind PWA - Stack Navigation System
 *
 * iOS/Android-style stack navigation with:
 * - Push/Pop page transitions
 * - Back gesture (swipe from left edge)
 * - Browser history integration
 * - Haptic feedback
 */

// Navigation Store for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.store('navigation', {
        stack: [],
        currentPage: null,
        isTransitioning: false,
        gestureState: {
            startX: 0,
            startY: 0,
            currentX: 0,
            isDragging: false,
            progress: 0
        },

        /**
         * Initialize navigation with current page
         */
        init(initialPage = null) {
            const page = initialPage || window.location.pathname;
            this.stack = [{ page, title: document.title, url: window.location.href }];
            this.currentPage = page;

            // Setup browser history listener
            this._setupPopStateListener();
        },

        /**
         * Push a new page onto the stack
         */
        push(page, options = {}) {
            if (this.isTransitioning) return;

            const pageData = {
                page,
                title: options.title || '',
                url: options.url || page,
                data: options.data || null,
                timestamp: Date.now()
            };

            this.stack.push(pageData);
            this.currentPage = page;

            // Browser history integration
            if (options.updateHistory !== false) {
                history.pushState(
                    { page, stackIndex: this.stack.length - 1 },
                    pageData.title,
                    pageData.url
                );
            }

            // Trigger enter animation
            this._triggerTransition('enter', 'push');

            // Haptic feedback
            this._haptic('light');

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('navigation:push', {
                detail: { page, options }
            }));
        },

        /**
         * Pop the current page from the stack
         */
        pop() {
            if (this.isTransitioning) return false;
            if (!this.canGoBack()) return false;

            const poppedPage = this.stack.pop();
            const previousPage = this.stack[this.stack.length - 1];
            this.currentPage = previousPage?.page || null;

            // Trigger leave animation
            this._triggerTransition('leave', 'pop');

            // Haptic feedback
            this._haptic('light');

            // Browser history integration
            if (poppedPage && history.state?.stackIndex === this.stack.length) {
                history.back();
            }

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('navigation:pop', {
                detail: { poppedPage, currentPage: this.currentPage }
            }));

            return true;
        },

        /**
         * Check if we can go back
         */
        canGoBack() {
            return this.stack.length > 1;
        },

        /**
         * Get the previous page data
         */
        getPreviousPage() {
            if (this.stack.length < 2) return null;
            return this.stack[this.stack.length - 2];
        },

        /**
         * Get current page data
         */
        getCurrentPage() {
            if (this.stack.length === 0) return null;
            return this.stack[this.stack.length - 1];
        },

        /**
         * Reset navigation stack
         */
        reset() {
            const currentPage = this.getCurrentPage();
            this.stack = currentPage ? [currentPage] : [];

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('navigation:reset'));
        },

        /**
         * Replace current page
         */
        replace(page, options = {}) {
            if (this.stack.length === 0) {
                this.push(page, options);
                return;
            }

            const pageData = {
                page,
                title: options.title || '',
                url: options.url || page,
                data: options.data || null,
                timestamp: Date.now()
            };

            this.stack[this.stack.length - 1] = pageData;
            this.currentPage = page;

            // Browser history integration
            if (options.updateHistory !== false) {
                history.replaceState(
                    { page, stackIndex: this.stack.length - 1 },
                    pageData.title,
                    pageData.url
                );
            }

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('navigation:replace', {
                detail: { page, options }
            }));
        },

        /**
         * Navigate to a specific index in the stack
         */
        popTo(index) {
            if (index < 0 || index >= this.stack.length) return false;

            const popsNeeded = this.stack.length - 1 - index;
            for (let i = 0; i < popsNeeded; i++) {
                this.stack.pop();
            }

            this.currentPage = this.stack[this.stack.length - 1]?.page || null;
            this._triggerTransition('leave', 'pop');

            return true;
        },

        /**
         * Pop to root (first page)
         */
        popToRoot() {
            return this.popTo(0);
        },

        /**
         * Setup popstate listener for browser back/forward
         */
        _setupPopStateListener() {
            window.addEventListener('popstate', (event) => {
                if (event.state?.stackIndex !== undefined) {
                    const targetIndex = event.state.stackIndex;

                    if (targetIndex < this.stack.length - 1) {
                        // Going back
                        this.popTo(targetIndex);
                    }
                }
            });
        },

        /**
         * Trigger page transition animation
         */
        _triggerTransition(type, direction) {
            this.isTransitioning = true;

            const pageContainer = document.querySelector('[data-page-container]');
            if (!pageContainer) {
                this.isTransitioning = false;
                return;
            }

            // Remove existing classes
            pageContainer.classList.remove(
                'page-enter', 'page-enter-active',
                'page-leave', 'page-leave-active'
            );

            // Force reflow
            void pageContainer.offsetWidth;

            // Add animation classes
            pageContainer.classList.add(`page-${type}`);

            requestAnimationFrame(() => {
                pageContainer.classList.add(`page-${type}-active`);
            });

            // Cleanup after animation
            setTimeout(() => {
                pageContainer.classList.remove(`page-${type}`, `page-${type}-active`);
                this.isTransitioning = false;
            }, 300);
        },

        /**
         * Haptic feedback helper
         */
        _haptic(type = 'light') {
            if (window.haptic) {
                window.haptic[type]();
            } else if (window.SmHaptic) {
                window.SmHaptic[type]();
            } else if (navigator.vibrate) {
                const patterns = {
                    light: 10,
                    medium: 20,
                    heavy: 40
                };
                navigator.vibrate(patterns[type] || 10);
            }
        }
    });
});


/**
 * Back Gesture Handler
 * Swipe from left edge to go back (iOS-style)
 */
class BackGestureHandler {
    constructor(options = {}) {
        this.edgeWidth = options.edgeWidth || 20; // px from left edge
        this.threshold = options.threshold || 100; // px swipe distance to trigger
        this.maxAngle = options.maxAngle || 30; // degrees from horizontal

        this.startX = 0;
        this.startY = 0;
        this.currentX = 0;
        this.currentY = 0;
        this.isDragging = false;
        this.isEdgeSwipe = false;

        this.previewElement = null;
        this.overlayElement = null;

        this._init();
    }

    _init() {
        // Only enable in PWA mode
        const isPWA = window.isPWAInstalled ||
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;

        if (!isPWA) return;

        this._createPreviewElements();
        this._bindEvents();
    }

    _createPreviewElements() {
        // Create overlay for darkening effect
        this.overlayElement = document.createElement('div');
        this.overlayElement.className = 'back-gesture-overlay';
        this.overlayElement.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0);
            z-index: 9998;
            pointer-events: none;
            transition: background 0.1s ease-out;
            display: none;
        `;
        document.body.appendChild(this.overlayElement);

        // Create preview indicator
        this.previewElement = document.createElement('div');
        this.previewElement.className = 'back-gesture-indicator';
        this.previewElement.innerHTML = `
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        `;
        this.previewElement.style.cssText = `
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%) translateX(-100%);
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.15s ease-out;
        `;
        document.body.appendChild(this.previewElement);
    }

    _bindEvents() {
        document.addEventListener('touchstart', this._onTouchStart.bind(this), { passive: true });
        document.addEventListener('touchmove', this._onTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this._onTouchEnd.bind(this), { passive: true });
        document.addEventListener('touchcancel', this._onTouchEnd.bind(this), { passive: true });
    }

    _onTouchStart(e) {
        const touch = e.touches[0];
        this.startX = touch.clientX;
        this.startY = touch.clientY;
        this.currentX = this.startX;
        this.currentY = this.startY;

        // Check if starting from left edge
        this.isEdgeSwipe = this.startX < this.edgeWidth;
        this.isDragging = false;
    }

    _onTouchMove(e) {
        if (!this.isEdgeSwipe) return;

        const touch = e.touches[0];
        this.currentX = touch.clientX;
        this.currentY = touch.clientY;

        const deltaX = this.currentX - this.startX;
        const deltaY = this.currentY - this.startY;

        // Check swipe angle (should be mostly horizontal)
        const angle = Math.abs(Math.atan2(deltaY, deltaX) * 180 / Math.PI);
        if (angle > this.maxAngle && !this.isDragging) {
            this.isEdgeSwipe = false;
            return;
        }

        // Check if navigation can go back
        const store = window.Alpine?.store('navigation');
        if (!store?.canGoBack()) {
            this.isEdgeSwipe = false;
            return;
        }

        // Only start dragging after minimal movement
        if (deltaX > 10 && !this.isDragging) {
            this.isDragging = true;
            this._showPreview();
        }

        if (!this.isDragging) return;

        // Prevent scrolling while swiping
        e.preventDefault();

        // Calculate progress (0 to 1)
        const progress = Math.min(1, Math.max(0, deltaX / this.threshold));
        this._updatePreview(progress, deltaX);
    }

    _onTouchEnd() {
        if (!this.isDragging) {
            this.isEdgeSwipe = false;
            return;
        }

        const deltaX = this.currentX - this.startX;
        const shouldNavigateBack = deltaX >= this.threshold;

        this._hidePreview();

        if (shouldNavigateBack) {
            // Trigger haptic feedback
            this._haptic('medium');

            // Navigate back
            const store = window.Alpine?.store('navigation');
            if (store) {
                store.pop();
            } else {
                // Fallback to browser back
                history.back();
            }
        }

        this.isDragging = false;
        this.isEdgeSwipe = false;
    }

    _showPreview() {
        if (this.overlayElement) {
            this.overlayElement.style.display = 'block';
        }
        if (this.previewElement) {
            this.previewElement.style.opacity = '1';
        }
    }

    _updatePreview(progress, deltaX) {
        // Update overlay darkness
        if (this.overlayElement) {
            this.overlayElement.style.background = `rgba(0, 0, 0, ${progress * 0.3})`;
        }

        // Update indicator position
        if (this.previewElement) {
            const indicatorX = Math.min(deltaX - 24, 24); // Max 24px from edge
            this.previewElement.style.transform = `translateY(-50%) translateX(${indicatorX}px)`;
            this.previewElement.style.opacity = String(Math.min(1, progress * 2));

            // Scale up when approaching threshold
            const scale = 1 + (progress * 0.2);
            this.previewElement.style.transform = `translateY(-50%) translateX(${indicatorX}px) scale(${scale})`;
        }

        // Update navigation store gesture state
        const store = window.Alpine?.store('navigation');
        if (store) {
            store.gestureState.progress = progress;
            store.gestureState.currentX = this.currentX;
            store.gestureState.isDragging = true;
        }
    }

    _hidePreview() {
        if (this.overlayElement) {
            this.overlayElement.style.background = 'rgba(0, 0, 0, 0)';
            setTimeout(() => {
                this.overlayElement.style.display = 'none';
            }, 150);
        }

        if (this.previewElement) {
            this.previewElement.style.opacity = '0';
            this.previewElement.style.transform = 'translateY(-50%) translateX(-100%)';
        }

        // Reset navigation store gesture state
        const store = window.Alpine?.store('navigation');
        if (store) {
            store.gestureState.progress = 0;
            store.gestureState.isDragging = false;
        }
    }

    _haptic(type = 'light') {
        if (window.haptic) {
            window.haptic[type]();
        } else if (window.SmHaptic) {
            window.SmHaptic[type]();
        } else if (navigator.vibrate) {
            const patterns = {
                light: 10,
                medium: 20,
                heavy: 40
            };
            navigator.vibrate(patterns[type] || 10);
        }
    }
}


/**
 * Page Transition Manager
 * Handles smooth page transitions
 */
class PageTransitionManager {
    constructor() {
        this._injectStyles();
    }

    _injectStyles() {
        if (document.getElementById('pwa-navigation-styles')) return;

        const style = document.createElement('style');
        style.id = 'pwa-navigation-styles';
        style.textContent = `
            /* Respect reduced motion preference */
            @media (prefers-reduced-motion: reduce) {
                .page-enter,
                .page-enter-active,
                .page-leave,
                .page-leave-active {
                    animation: none !important;
                    transition: none !important;
                    transform: none !important;
                    opacity: 1 !important;
                }
            }

            /* Push animation (new page slides in from right) */
            .page-enter {
                transform: translateX(100%);
                opacity: 0;
            }

            .page-enter-active {
                transform: translateX(0);
                opacity: 1;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Pop animation (current page slides out to right) */
            .page-leave {
                transform: translateX(0);
                opacity: 1;
            }

            .page-leave-active {
                transform: translateX(100%);
                opacity: 0;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Page container base styles */
            [data-page-container] {
                will-change: transform, opacity;
            }

            /* Prevent body scroll during gesture */
            body.gesture-active {
                overflow: hidden;
                touch-action: none;
            }

            /* Back gesture preview styles */
            .back-gesture-active [data-page-container] {
                transform: translateX(var(--gesture-offset, 0));
                transition: none;
            }

            /* Shadow effect during back gesture */
            .back-gesture-shadow {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 20px;
                background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
                pointer-events: none;
                z-index: 9997;
                opacity: 0;
                transition: opacity 0.15s ease-out;
            }

            body.gesture-active .back-gesture-shadow {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    }
}


// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize page transition manager
    new PageTransitionManager();

    // Initialize back gesture handler
    new BackGestureHandler({
        edgeWidth: 20,
        threshold: 100,
        maxAngle: 30
    });

    // Initialize navigation store if Alpine is ready
    if (window.Alpine) {
        const store = Alpine.store('navigation');
        if (store) {
            store.init();
        }
    }
});


// Export for module usage
export { BackGestureHandler, PageTransitionManager };
