/**
 * Haptic Feedback System for Native-like Experience
 *
 * Provides tactile feedback using Vibration API
 * Works on iOS (Safari 13+) and Android (Chrome, Firefox)
 */

class HapticFeedback {
    constructor() {
        this.isSupported = 'vibrate' in navigator;
        this.isEnabled = true;

        // Load preference from localStorage
        const savedPreference = localStorage.getItem('haptic_enabled');
        if (savedPreference !== null) {
            this.isEnabled = savedPreference === 'true';
        }
    }

    /**
     * Enable haptic feedback
     */
    enable() {
        this.isEnabled = true;
        localStorage.setItem('haptic_enabled', 'true');
    }

    /**
     * Disable haptic feedback
     */
    disable() {
        this.isEnabled = false;
        localStorage.setItem('haptic_enabled', 'false');
    }

    /**
     * Toggle haptic feedback
     */
    toggle() {
        if (this.isEnabled) {
            this.disable();
        } else {
            this.enable();
        }
        return this.isEnabled;
    }

    /**
     * Check if haptic is supported and enabled
     */
    canVibrate() {
        return this.isSupported && this.isEnabled;
    }

    /**
     * Light tap - for button presses, switches
     * Duration: 10ms
     */
    light() {
        if (!this.canVibrate()) return;
        navigator.vibrate(10);
    }

    /**
     * Medium tap - for selections, confirmations
     * Duration: 20ms
     */
    medium() {
        if (!this.canVibrate()) return;
        navigator.vibrate(20);
    }

    /**
     * Heavy tap - for important actions, alerts
     * Duration: 40ms
     */
    heavy() {
        if (!this.canVibrate()) return;
        navigator.vibrate(40);
    }

    /**
     * Success pattern - for successful operations
     * Pattern: short-pause-short
     */
    success() {
        if (!this.canVibrate()) return;
        navigator.vibrate([10, 50, 10]);
    }

    /**
     * Error pattern - for errors, warnings
     * Pattern: long-pause-long-pause-long
     */
    error() {
        if (!this.canVibrate()) return;
        navigator.vibrate([30, 50, 30, 50, 30]);
    }

    /**
     * Notification pattern - for new messages, updates
     * Pattern: short-short
     */
    notification() {
        if (!this.canVibrate()) return;
        navigator.vibrate([15, 30, 15]);
    }

    /**
     * Selection pattern - for selecting items in a list
     * Pattern: very short
     */
    selection() {
        if (!this.canVibrate()) return;
        navigator.vibrate(5);
    }

    /**
     * Impact pattern - for drag and drop, impactful actions
     * Pattern: medium
     */
    impact() {
        if (!this.canVibrate()) return;
        navigator.vibrate(25);
    }

    /**
     * Custom vibration pattern
     * @param {number|number[]} pattern - Duration or pattern array
     */
    custom(pattern) {
        if (!this.canVibrate()) return;
        navigator.vibrate(pattern);
    }
}

// Create singleton instance
const haptic = new HapticFeedback();

// Expose globally
window.haptic = haptic;

// Alpine.js integration (if available)
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        Alpine.magic('haptic', () => haptic);
    });
}

// Export for module usage
export default haptic;

// Auto-attach to common elements
document.addEventListener('DOMContentLoaded', () => {
    // Auto-vibrate on button clicks (if they have data-haptic attribute)
    document.addEventListener('click', (e) => {
        const target = e.target.closest('[data-haptic]');
        if (!target) return;

        const hapticType = target.dataset.haptic || 'light';

        switch (hapticType) {
            case 'light':
                haptic.light();
                break;
            case 'medium':
                haptic.medium();
                break;
            case 'heavy':
                haptic.heavy();
                break;
            case 'success':
                haptic.success();
                break;
            case 'error':
                haptic.error();
                break;
            case 'selection':
                haptic.selection();
                break;
            case 'impact':
                haptic.impact();
                break;
            default:
                haptic.light();
        }
    });

});
