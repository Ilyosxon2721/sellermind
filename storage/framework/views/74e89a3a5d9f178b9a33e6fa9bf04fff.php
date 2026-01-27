

<div id="splash-screen"
     x-data="splashScreen()"
     x-show="isVisible"
     x-transition:leave="transition ease-in duration-500"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="splash-screen fixed inset-0 z-[9999] bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 flex flex-col items-center justify-center">

    
    <div class="splash-logo mb-6 animate-bounce-slow">
        <div class="w-28 h-28 bg-white/20 backdrop-blur-md rounded-3xl flex items-center justify-center shadow-2xl">
            <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 2.18l8 4V17c0 4.52-3.03 8.77-7.5 10-4.47-1.23-7.5-5.48-7.5-10V8.18l7-4z"/>
                <path d="M12 6L6 9v6c0 3.31 2.28 6.41 5.5 7.33 3.22-.92 5.5-4.02 5.5-7.33V9l-5-3zm0 1.41l4 2.27v5.32c0 2.62-1.81 5.07-4 5.82-2.19-.75-4-3.2-4-5.82V9.68l4-2.27z"/>
            </svg>
        </div>
    </div>

    
    <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">
        SellerMind
    </h1>

    
    <p class="text-white/80 text-sm mb-8">
        Управление продажами на маркетплейсах
    </p>

    
    <div class="relative">
        
        <div class="w-10 h-10 border-3 border-white/30 border-t-white rounded-full animate-spin"></div>

        
        <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-2">
            <div class="w-2 h-2 bg-white/60 rounded-full animate-pulse" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-white/60 rounded-full animate-pulse" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-white/60 rounded-full animate-pulse" style="animation-delay: 300ms"></div>
        </div>
    </div>

    
    <div class="absolute bottom-8 text-white/50 text-xs">
        v1.0.0
    </div>

</div>

<script>
function splashScreen() {
    return {
        isVisible: true,
        minDisplayTime: 1500, // Minimum 1.5 seconds
        startTime: Date.now(),

        init() {
            // Only show splash in PWA mode
            const isPWA = window.isPWAInstalled ||
                         window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone === true;

            if (!isPWA) {
                // Not PWA mode - hide immediately
                this.isVisible = false;
                return;
            }

            // Wait for page to load
            if (document.readyState === 'complete') {
                this.hide();
            } else {
                window.addEventListener('load', () => this.hide());
            }

            // Fallback: hide after 5 seconds max
            setTimeout(() => {
                if (this.isVisible) {
                    console.warn('Splash screen timeout - force hiding');
                    this.isVisible = false;
                }
            }, 5000);
        },

        hide() {
            const elapsed = Date.now() - this.startTime;
            const remaining = Math.max(0, this.minDisplayTime - elapsed);

            // Ensure splash shows for minimum time for better UX
            setTimeout(() => {
                this.isVisible = false;

                // Dispatch event
                window.dispatchEvent(new CustomEvent('splash-hidden'));

                console.log('✅ Splash screen hidden');
            }, remaining);
        }
    };
}

// Custom animation
const style = document.createElement('style');
style.textContent = `
    @keyframes bounce-slow {
        0%, 100% {
            transform: translateY(0) scale(1);
        }
        50% {
            transform: translateY(-10px) scale(1.05);
        }
    }

    .animate-bounce-slow {
        animation: bounce-slow 2s ease-in-out infinite;
    }
`;
document.head.appendChild(style);

// Pure JS fallback - hide splash after 4 seconds regardless of Alpine.js
(function() {
    const hideSplash = () => {
        const splash = document.getElementById('splash-screen');
        if (splash && splash.style.display !== 'none') {
            splash.style.transition = 'opacity 0.5s ease';
            splash.style.opacity = '0';
            setTimeout(() => {
                splash.style.display = 'none';
                console.log('✅ Splash hidden via JS fallback');
            }, 500);
        }
    };

    // Fallback after 4 seconds
    setTimeout(hideSplash, 4000);

    // Also try when page loads
    if (document.readyState === 'complete') {
        setTimeout(hideSplash, 2000);
    } else {
        window.addEventListener('load', () => setTimeout(hideSplash, 2000));
    }
})();
</script>

<style>
/* Additional splash screen styles */
#splash-screen {
    /* Prevent scrolling behind splash */
    position: fixed;
    overscroll-behavior: contain;
}

/* Smooth fade out */
#splash-screen[style*="opacity: 0"] {
    pointer-events: none;
}
</style>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/splash-screen.blade.php ENDPATH**/ ?>