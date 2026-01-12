/**
 * Native Page Transitions for PWA
 *
 * Provides iOS/Android-style page transitions
 * Works with browser navigation events
 */

class PageTransitions {
    constructor() {
        this.isNavigating = false;
        this.animationDuration = 300;
        this.previousUrl = window.location.href;

        this.init();
    }

    init() {
        // Only enable in PWA mode
        const isPWA = window.isPWAInstalled ||
                     window.matchMedia('(display-mode: standalone)').matches ||
                     window.navigator.standalone === true;

        if (!isPWA) {
            console.log('⏭️  Page Transitions: Disabled (not in PWA mode)');
            return;
        }

        // Detect navigation direction
        this.setupNavigationDetection();

        console.log('✅ Page Transitions: Enabled');
    }

    setupNavigationDetection() {
        // Intercept link clicks for smooth transitions
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');

            if (!link) return;
            if (link.target === '_blank') return;
            if (link.download) return;
            if (link.rel === 'external') return;

            const url = link.href;

            // Check if it's an internal link
            if (!url || !url.startsWith(window.location.origin)) return;

            // Ignore if same page
            if (url === window.location.href) return;

            // Prevent default and handle transition
            e.preventDefault();
            this.navigateWithTransition(url, 'forward');
        });

        // Handle browser back/forward
        window.addEventListener('popstate', (e) => {
            const url = window.location.href;
            const direction = this.getNavigationDirection(url);

            this.applyTransition(direction);
        });
    }

    getNavigationDirection(newUrl) {
        // Simple heuristic: if URL is shorter, it's probably going back
        if (newUrl.length < this.previousUrl.length) {
            return 'back';
        }
        return 'forward';
    }

    async navigateWithTransition(url, direction = 'forward') {
        if (this.isNavigating) return;

        this.isNavigating = true;

        // Apply exit animation
        await this.applyTransition(direction, 'exit');

        // Haptic feedback
        if (window.haptic) {
            window.haptic.light();
        }

        // Navigate
        this.previousUrl = window.location.href;
        window.location.href = url;
    }

    async applyTransition(direction, phase = 'enter') {
        const body = document.body;

        // Remove any existing animation classes
        body.classList.remove(
            'page-transition-enter',
            'page-transition-exit',
            'page-transition-forward',
            'page-transition-back'
        );

        // Force reflow
        void body.offsetWidth;

        // Add animation classes
        body.classList.add(`page-transition-${phase}`);
        body.classList.add(`page-transition-${direction}`);

        // Wait for animation
        await new Promise(resolve => setTimeout(resolve, this.animationDuration));

        // Cleanup
        if (phase === 'exit') {
            this.isNavigating = false;
        }
    }
}

// Initialize
const pageTransitions = new PageTransitions();

// Export
export default pageTransitions;

// Add CSS for transitions
const style = document.createElement('style');
style.textContent = `
    /* Disable default page transitions if user prefers reduced motion */
    @media (prefers-reduced-motion: reduce) {
        .page-transition-enter,
        .page-transition-exit {
            animation: none !important;
            transition: none !important;
        }
    }

    /* Page transition base */
    body {
        transition: none;
    }

    /* Forward navigation (slide from right) */
    .page-transition-exit.page-transition-forward {
        animation: slideOutLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .page-transition-enter.page-transition-forward {
        animation: slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Back navigation (slide to right) */
    .page-transition-exit.page-transition-back {
        animation: slideOutRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .page-transition-enter.page-transition-back {
        animation: slideInLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Keyframes */
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutLeft {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(-30%);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }

    /* Fade transition (alternative) */
    .page-transition-fade {
        animation: fadeTransition 0.3s ease-in-out;
    }

    @keyframes fadeTransition {
        0% { opacity: 1; }
        50% { opacity: 0; }
        100% { opacity: 1; }
    }

    /* Ensure smooth rendering */
    body.page-transition-enter,
    body.page-transition-exit {
        overflow-x: hidden;
    }
`;
document.head.appendChild(style);

console.log('✅ Page Transitions: Loaded');
