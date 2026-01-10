/**
 * Product Bulk Operations
 * Handles export, import, and bulk actions for products
 */

window.productBulkMixin = {
    // Bulk selection
    selectedVariants: [],
    selectAll: false,

    // Import
    showImportModal: false,
    importStep: 1,
    importFile: null,
    importPreview: null,
    importLoading: false,
    isDragging: false,

    // Bulk actions
    showBulkPriceModal: false,
    bulkPriceForm: {
        retail_price: '',
        purchase_price: '',
        old_price: '',
    },
    bulkActionLoading: false,

    /**
     * Export products to CSV
     */
    async exportProducts() {
        try {
            const params = {};

            // If products are selected, export only those
            if (this.selectedVariants.length > 0) {
                // Get unique product IDs from selected variants
                const productIds = [...new Set(
                    this.selectedVariants.map(vid => {
                        const variant = this.findVariantById(vid);
                        return variant?.product_id;
                    }).filter(Boolean)
                )];
                params.product_ids = productIds;
            }

            // Add filters
            if (this.search) {
                params.search = this.search;
            }

            const response = await fetch('/api/products/bulk/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.api.getToken()}`,
                },
                body: JSON.stringify(params),
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            // Download file
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `products_export_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showToast('success', 'Товары экспортированы');
        } catch (error) {
            console.error('Export error:', error);
            this.showToast('error', 'Ошибка экспорта: ' + error.message);
        }
    },

    /**
     * Open import modal
     */
    openImportModal() {
        this.showImportModal = true;
        this.importStep = 1;
        this.importFile = null;
        this.importPreview = null;
    },

    /**
     * Handle file select
     */
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.importFile = file;
        }
    },

    /**
     * Handle drag & drop
     */
    handleDrop(event) {
        this.isDragging = false;
        const file = event.dataTransfer.files[0];
        if (file && (file.name.endsWith('.csv') || file.name.endsWith('.txt'))) {
            this.importFile = file;
        } else {
            this.showToast('error', 'Пожалуйста, загрузите CSV файл');
        }
    },

    /**
     * Preview import changes
     */
    async previewImport() {
        if (!this.importFile) return;

        this.importLoading = true;

        try {
            const formData = new FormData();
            formData.append('file', this.importFile);

            const response = await fetch('/api/products/bulk/import/preview', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${window.api.getToken()}`,
                },
                body: formData,
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Preview failed');
            }

            this.importPreview = await response.json();
            this.importStep = 2;
        } catch (error) {
            console.error('Preview error:', error);
            this.showToast('error', 'Ошибка preview: ' + error.message);
        } finally {
            this.importLoading = false;
        }
    },

    /**
     * Apply import
     */
    async applyImport() {
        if (!this.importFile) return;

        if (this.importPreview?.errors_count > 0) {
            this.showToast('error', 'Исправьте ошибки перед применением');
            return;
        }

        if (!confirm(`Применить ${this.importPreview?.changes_count} изменений?`)) {
            return;
        }

        this.importLoading = true;

        try {
            const formData = new FormData();
            formData.append('file', this.importFile);

            const response = await fetch('/api/products/bulk/import/apply', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${window.api.getToken()}`,
                },
                body: formData,
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Import failed');
            }

            const result = await response.json();
            this.showToast('success', result.message);
            this.showImportModal = false;

            // Reload products after some delay
            setTimeout(() => {
                this.loadProducts();
            }, 2000);
        } catch (error) {
            console.error('Import error:', error);
            this.showToast('error', 'Ошибка импорта: ' + error.message);
        } finally {
            this.importLoading = false;
        }
    },

    /**
     * Format file size
     */
    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },

    /**
     * Toggle variant selection
     */
    toggleVariant(variantId) {
        const index = this.selectedVariants.indexOf(variantId);
        if (index > -1) {
            this.selectedVariants.splice(index, 1);
        } else {
            this.selectedVariants.push(variantId);
        }
    },

    /**
     * Toggle select all
     */
    toggleSelectAll() {
        if (this.selectAll) {
            // Select all visible variants
            this.products.forEach(product => {
                if (product.variants) {
                    product.variants.forEach(variant => {
                        if (!this.selectedVariants.includes(variant.id)) {
                            this.selectedVariants.push(variant.id);
                        }
                    });
                }
            });
        } else {
            // Deselect all
            this.selectedVariants = [];
        }
    },

    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedVariants = [];
        this.selectAll = false;
    },

    /**
     * Check if variant is selected
     */
    isVariantSelected(variantId) {
        return this.selectedVariants.includes(variantId);
    },

    /**
     * Find variant by ID
     */
    findVariantById(variantId) {
        for (const product of this.products) {
            if (product.variants) {
                const variant = product.variants.find(v => v.id === variantId);
                if (variant) {
                    return { ...variant, product_id: product.id };
                }
            }
        }
        return null;
    },

    /**
     * Bulk action
     */
    async bulkAction(action) {
        if (this.selectedVariants.length === 0) {
            this.showToast('error', 'Выберите товары');
            return;
        }

        const confirmMessages = {
            activate: `Активировать ${this.selectedVariants.length} товаров?`,
            deactivate: `Деактивировать ${this.selectedVariants.length} товаров?`,
        };

        if (!confirm(confirmMessages[action])) {
            return;
        }

        this.bulkActionLoading = true;

        try {
            const response = await fetch('/api/products/bulk/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.api.getToken()}`,
                },
                body: JSON.stringify({
                    variant_ids: this.selectedVariants,
                    action: action,
                }),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Bulk action failed');
            }

            const result = await response.json();
            this.showToast('success', `Обновлено: ${result.updated_count} товаров`);
            this.clearSelection();
            this.loadProducts();
        } catch (error) {
            console.error('Bulk action error:', error);
            this.showToast('error', 'Ошибка: ' + error.message);
        } finally {
            this.bulkActionLoading = false;
        }
    },

    /**
     * Bulk update prices
     */
    async bulkUpdatePrices() {
        if (this.selectedVariants.length === 0) {
            this.showToast('error', 'Выберите товары');
            return;
        }

        const data = {};
        if (this.bulkPriceForm.retail_price) {
            data.retail_price = parseFloat(this.bulkPriceForm.retail_price);
        }
        if (this.bulkPriceForm.purchase_price) {
            data.purchase_price = parseFloat(this.bulkPriceForm.purchase_price);
        }
        if (this.bulkPriceForm.old_price) {
            data.old_price = parseFloat(this.bulkPriceForm.old_price);
        }

        if (Object.keys(data).length === 0) {
            this.showToast('error', 'Укажите хотя бы одну цену');
            return;
        }

        if (!confirm(`Изменить цены для ${this.selectedVariants.length} товаров?`)) {
            return;
        }

        this.bulkActionLoading = true;

        try {
            const response = await fetch('/api/products/bulk/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.api.getToken()}`,
                },
                body: JSON.stringify({
                    variant_ids: this.selectedVariants,
                    action: 'update_prices',
                    data: data,
                }),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Price update failed');
            }

            const result = await response.json();
            this.showToast('success', `Обновлено: ${result.updated_count} товаров`);
            this.showBulkPriceModal = false;
            this.bulkPriceForm = {
                retail_price: '',
                purchase_price: '',
                old_price: '',
            };
            this.clearSelection();
            this.loadProducts();
        } catch (error) {
            console.error('Price update error:', error);
            this.showToast('error', 'Ошибка: ' + error.message);
        } finally {
            this.bulkActionLoading = false;
        }
    },

    /**
     * Show toast notification
     */
    showToast(type, message) {
        // Use existing toast system if available
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else {
            // Fallback to alert
            alert(message);
        }
    },
};
