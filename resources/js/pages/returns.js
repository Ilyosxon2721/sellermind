/**
 * Alpine компонент страницы управления возвратами
 * company_id передаётся через data-company-id атрибут на корневом элементе
 */
window.returnsPage = function returnsPage() {
    return {
        loading: false,
        returns: [],
        stats: {},
        meta: { current_page: 1, last_page: 1 },
        filters: { status: 'pending', order_type: '' },
        toast: { show: false, message: '', type: 'success' },

        getCompanyId() {
            return Alpine.store('auth')?.currentCompany?.id
                || Alpine.store('auth')?.user?.company_id
                || parseInt(this.$el?.dataset?.companyId)
                || null;
        },

        async init() {
            await Promise.all([this.loadData(), this.loadStats()]);
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    company_id: this.getCompanyId(),
                    page: this.meta.current_page,
                });
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.order_type) params.append('order_type', this.filters.order_type);

                const res = await fetch('/api/marketplace/returns?' + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.returns = data.returns ?? [];
                this.meta = data.meta ?? { current_page: 1, last_page: 1 };

                await this.loadOrderItems();
            } catch (e) {
                this.showToast('Ошибка загрузки: ' + e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const res = await fetch('/api/marketplace/returns/stats?company_id=' + this.getCompanyId(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.stats = data.stats ?? {};
            } catch (e) {}
        },

        async loadOrderItems() {
            const pending = this.returns.filter(r => r.status === 'pending' && !r.order_items);
            for (const item of pending) {
                try {
                    const res = await fetch('/api/marketplace/returns/' + item.id, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    item.order_items = data.return?.order?.items ?? [];
                } catch (e) {
                    item.order_items = [];
                }
            }
        },

        async returnToStock(item) {
            if (!confirm('Принять товар на склад? Остатки будут восстановлены.')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/marketplace/returns/' + item.id + '/return-to-stock', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Товар принят на склад. Остатки восстановлены.', 'success');
                    item.status = 'processed';
                    item.action = 'return_to_stock';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.returned_to_stock = (this.stats.returned_to_stock ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async writeOff(item) {
            if (!confirm('Списать товар? Остатки не будут восстановлены.')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/marketplace/returns/' + item.id + '/write-off', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Товар списан.', 'success');
                    item.status = 'processed';
                    item.action = 'write_off';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.written_off = (this.stats.written_off ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async reject(item) {
            if (!confirm('Отклонить запись возврата?')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/marketplace/returns/' + item.id + '/reject', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Возврат отклонён.', 'success');
                    item.status = 'rejected';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.rejected = (this.stats.rejected ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async changePage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            await this.loadData();
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 4000);
        },
    };
};
