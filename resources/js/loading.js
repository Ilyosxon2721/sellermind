/**
 * Global Loading Indicator
 *
 * Shows/hides full-page loading overlay
 */

class LoadingIndicator {
    constructor() {
        this.isShowing = false;
        this.queue = 0; // Track multiple loading requests
    }

    show() {
        this.queue++;
        if (!this.isShowing) {
            this.isShowing = true;
            window.dispatchEvent(new CustomEvent('loading-start'));
        }
    }

    hide() {
        this.queue = Math.max(0, this.queue - 1);
        if (this.queue === 0 && this.isShowing) {
            this.isShowing = false;
            window.dispatchEvent(new CustomEvent('loading-end'));
        }
    }

    hideAll() {
        this.queue = 0;
        this.isShowing = false;
        window.dispatchEvent(new CustomEvent('loading-end'));
    }
}

// Create global instance
const loading = new LoadingIndicator();

// Export for module usage
export default loading;

// Global access
window.loading = loading;

// Add axios interceptors to show loading automatically
if (window.axios) {
    let requestCount = 0;

    window.axios.interceptors.request.use(
        (config) => {
            // Don't show loading for background requests
            if (!config.silent) {
                requestCount++;
                if (requestCount === 1) {
                    loading.show();
                }
            }
            return config;
        },
        (error) => {
            requestCount = Math.max(0, requestCount - 1);
            if (requestCount === 0) {
                loading.hide();
            }
            return Promise.reject(error);
        }
    );

    window.axios.interceptors.response.use(
        (response) => {
            if (!response.config.silent) {
                requestCount = Math.max(0, requestCount - 1);
                if (requestCount === 0) {
                    loading.hide();
                }
            }
            return response;
        },
        (error) => {
            if (!error.config?.silent) {
                requestCount = Math.max(0, requestCount - 1);
                if (requestCount === 0) {
                    loading.hide();
                }
            }
            return Promise.reject(error);
        }
    );
}
