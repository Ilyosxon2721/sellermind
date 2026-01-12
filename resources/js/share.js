/**
 * Web Share API Integration
 *
 * Native sharing functionality for mobile devices
 * Provides fallback for desktop browsers
 */

class ShareManager {
    constructor() {
        this.supported = this.isSupported();
        this.init();
    }

    init() {
        if (this.supported) {
            console.log('✅ Web Share API: Supported');
        } else {
            console.log('⏭️  Web Share API: Not supported, using fallback');
        }
    }

    /**
     * Check if Web Share API is supported
     * @returns {boolean}
     */
    isSupported() {
        return 'share' in navigator;
    }

    /**
     * Check if specific data can be shared
     * @param {Object} data - Share data to validate
     * @returns {boolean}
     */
    canShare(data = {}) {
        if (!this.supported) return false;

        try {
            return navigator.canShare ? navigator.canShare(data) : true;
        } catch (error) {
            console.error('Share validation error:', error);
            return false;
        }
    }

    /**
     * Share content using native share dialog
     * @param {Object} options - Share options
     * @returns {Promise}
     */
    async share(options = {}) {
        const data = {
            title: options.title || document.title,
            text: options.text || '',
            url: options.url || window.location.href,
        };

        // Add files if supported and provided
        if (options.files && Array.isArray(options.files) && options.files.length > 0) {
            data.files = options.files;
        }

        // Check if we can share
        if (!this.canShare(data)) {
            console.warn('Cannot share this data, using fallback');
            return this.fallbackShare(data);
        }

        try {
            await navigator.share(data);

            // Haptic feedback
            if (window.haptic) {
                window.haptic.success();
            }

            // Show success toast
            if (window.toast) {
                window.toast.success('Успешно отправлено');
            }

            return { success: true };
        } catch (error) {
            // User cancelled or error occurred
            if (error.name === 'AbortError') {
                // User cancelled - this is normal, no error
                console.log('Share cancelled by user');
                return { success: false, cancelled: true };
            }

            console.error('Share error:', error);

            // Haptic feedback
            if (window.haptic) {
                window.haptic.error();
            }

            // Try fallback
            return this.fallbackShare(data);
        }
    }

    /**
     * Fallback sharing for unsupported browsers
     * @param {Object} data - Share data
     * @returns {Promise}
     */
    async fallbackShare(data) {
        const shareUrl = data.url || window.location.href;
        const shareText = data.text ? `${data.text}\n\n` : '';
        const shareTitle = data.title ? `${data.title}\n` : '';
        const fullText = `${shareTitle}${shareText}${shareUrl}`;

        try {
            // Try copying to clipboard
            await navigator.clipboard.writeText(fullText);

            // Show success toast
            if (window.toast) {
                window.toast.success('Ссылка скопирована в буфер обмена', {
                    action: {
                        label: 'Понятно',
                        handler: () => {}
                    }
                });
            }

            // Haptic feedback
            if (window.haptic) {
                window.haptic.success();
            }

            return { success: true, fallback: true };
        } catch (error) {
            console.error('Clipboard fallback error:', error);

            // Last resort: show text in modal
            if (window.toast) {
                window.toast.info('Скопируйте ссылку вручную', {
                    duration: 0,
                    action: {
                        label: 'Закрыть',
                        handler: () => {}
                    }
                });
            }

            return { success: false, error };
        }
    }

    /**
     * Share product
     * @param {Object} product - Product data
     * @returns {Promise}
     */
    async shareProduct(product) {
        const url = product.url || `${window.location.origin}/products/${product.id}`;

        return this.share({
            title: product.name || product.title || 'Товар',
            text: product.description || 'Посмотрите этот товар!',
            url: url
        });
    }

    /**
     * Share order
     * @param {Object} order - Order data
     * @returns {Promise}
     */
    async shareOrder(order) {
        const url = order.url || `${window.location.origin}/orders/${order.id}`;

        return this.share({
            title: `Заказ #${order.id || order.number}`,
            text: order.description || 'Информация о заказе',
            url: url
        });
    }

    /**
     * Share dialog/chat
     * @param {Object} dialog - Dialog data
     * @returns {Promise}
     */
    async shareDialog(dialog) {
        const url = dialog.url || `${window.location.origin}/dialogs/${dialog.id}`;

        return this.share({
            title: dialog.title || 'Диалог',
            text: dialog.summary || 'Поделиться диалогом',
            url: url
        });
    }

    /**
     * Share current page
     * @param {Object} options - Override options
     * @returns {Promise}
     */
    async sharePage(options = {}) {
        return this.share({
            title: options.title || document.title,
            text: options.text || '',
            url: options.url || window.location.href
        });
    }

    /**
     * Share image
     * @param {File|Blob} imageFile - Image file to share
     * @param {Object} options - Additional options
     * @returns {Promise}
     */
    async shareImage(imageFile, options = {}) {
        if (!imageFile) {
            console.error('No image file provided');
            return { success: false, error: 'No image' };
        }

        // Create File object if it's a Blob
        let file = imageFile;
        if (!(imageFile instanceof File) && imageFile instanceof Blob) {
            file = new File([imageFile], options.filename || 'image.png', {
                type: imageFile.type || 'image/png'
            });
        }

        return this.share({
            title: options.title || 'Изображение',
            text: options.text || '',
            files: [file]
        });
    }

    /**
     * Share with specific service (fallback method)
     * @param {string} service - Service name (telegram, whatsapp, email, etc.)
     * @param {Object} data - Share data
     */
    shareVia(service, data) {
        const url = data.url || window.location.href;
        const text = encodeURIComponent(data.text || '');
        const title = encodeURIComponent(data.title || '');

        const services = {
            telegram: `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${text}`,
            whatsapp: `https://wa.me/?text=${text}%20${encodeURIComponent(url)}`,
            twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${text}`,
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
            vk: `https://vk.com/share.php?url=${encodeURIComponent(url)}&title=${title}`,
            email: `mailto:?subject=${title}&body=${text}%20${encodeURIComponent(url)}`,
            copy: null // Special case - handled separately
        };

        if (service === 'copy') {
            const fullText = `${data.title ? data.title + '\n' : ''}${data.text ? data.text + '\n\n' : ''}${url}`;
            return navigator.clipboard.writeText(fullText);
        }

        const serviceUrl = services[service];
        if (serviceUrl) {
            window.open(serviceUrl, '_blank', 'noopener,noreferrer');
            return Promise.resolve({ success: true, service });
        } else {
            console.error('Unknown service:', service);
            return Promise.reject({ success: false, error: 'Unknown service' });
        }
    }
}

// Initialize global instance
const share = new ShareManager();

// Alpine.js integration
if (window.Alpine) {
    document.addEventListener('alpine:init', () => {
        // Alpine magic helper
        Alpine.magic('share', () => share);

        // Alpine store
        Alpine.store('share', {
            supported: share.supported,

            share(options) {
                return share.share(options);
            },

            shareProduct(product) {
                return share.shareProduct(product);
            },

            shareOrder(order) {
                return share.shareOrder(order);
            },

            shareDialog(dialog) {
                return share.shareDialog(dialog);
            },

            sharePage(options) {
                return share.sharePage(options);
            },

            shareImage(imageFile, options) {
                return share.shareImage(imageFile, options);
            },

            shareVia(service, data) {
                return share.shareVia(service, data);
            }
        });

        // Alpine directive for simple share buttons
        Alpine.directive('share', (el, { expression, modifiers }, { evaluate }) => {
            el.addEventListener('click', async () => {
                const data = expression ? evaluate(expression) : {};

                // Haptic feedback
                if (window.haptic) {
                    window.haptic.light();
                }

                // Determine share type from modifiers
                if (modifiers.includes('product') && data) {
                    await share.shareProduct(data);
                } else if (modifiers.includes('order') && data) {
                    await share.shareOrder(data);
                } else if (modifiers.includes('dialog') && data) {
                    await share.shareDialog(data);
                } else if (modifiers.includes('page')) {
                    await share.sharePage(data);
                } else {
                    await share.share(data);
                }
            });
        });
    });
}

// Global access
window.share = share;

// Export
export default share;

console.log('✅ Web Share API: Loaded');
