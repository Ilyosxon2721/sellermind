@pushonce('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function marketplaceSeoMixin() {
    return {
        seoModalOpen: false,
        seoLoading: false,
        seoResult: null,
        seoLanguage: 'ru',
        seocopied: null,
        titleApplied: false,
        titleApplying: false,
        seoHistory: [],
        seoBothLoading: false,
        seoResultBoth: { ru: null, uz: null },
        seoBothMode: false,
        selectedProductForSeo: null,

        async _seoJson(res) {
            const text = await res.text();
            try { return JSON.parse(text); } catch { throw new Error('Не JSON ответ'); }
        },

        openSeoModal(product) {
            this.selectedProductForSeo = product;
            this.seoResult = null;
            this.seocopied = null;
            this.titleApplied = false;
            this.seoBothMode = false;
            this.seoHistory = [];
            this.seoModalOpen = true;
        },

        async runSeoOptimize() {
            if (!this.selectedProductForSeo?.id || this.seoLoading) return;
            this.seoLoading = true; this.seoBothMode = false; this.titleApplied = false; this.seoResult = null;
            try {
                const res = await fetch(`/api/marketplace/products/${this.selectedProductForSeo.id}/seo-optimize`, {
                    method: 'POST', headers: this.getHeaders(), credentials: 'include',
                    body: JSON.stringify({ language: this.seoLanguage }),
                });
                const data = await this._seoJson(res);
                if (!res.ok) throw new Error(data.message || `Ошибка ${res.status}`);
                this.seoResult = data.result;
                this.seoHistory.unshift({ result: data.result, language: this.seoLanguage, ts: Date.now() });
                if (this.seoHistory.length > 5) this.seoHistory = this.seoHistory.slice(0, 5);
            } catch (e) { alert(e.message || 'Ошибка AI оптимизации'); }
            finally { this.seoLoading = false; }
        },

        async runSeoBoth() {
            if (!this.selectedProductForSeo?.id || this.seoBothLoading) return;
            this.seoBothLoading = true; this.seoBothMode = true; this.seoResult = null;
            this.seoResultBoth = { ru: null, uz: null }; this.titleApplied = false;
            try {
                const [resRu, resUz] = await Promise.all([
                    fetch(`/api/marketplace/products/${this.selectedProductForSeo.id}/seo-optimize`, { method: 'POST', headers: this.getHeaders(), credentials: 'include', body: JSON.stringify({ language: 'ru' }) }),
                    fetch(`/api/marketplace/products/${this.selectedProductForSeo.id}/seo-optimize`, { method: 'POST', headers: this.getHeaders(), credentials: 'include', body: JSON.stringify({ language: 'uz' }) }),
                ]);
                const [dataRu, dataUz] = await Promise.all([this.safeJson(resRu), this.safeJson(resUz)]);
                this.seoResultBoth = { ru: dataRu.result, uz: dataUz.result };
                this.seoLanguage = 'ru'; this.seoResult = this.seoResultBoth.ru;
                this.seoHistory.unshift({ result: dataUz.result, language: 'uz', ts: Date.now() });
                this.seoHistory.unshift({ result: dataRu.result, language: 'ru', ts: Date.now() });
                if (this.seoHistory.length > 5) this.seoHistory = this.seoHistory.slice(0, 5);
            } catch (e) { alert(e.message || 'Ошибка генерации'); this.seoBothMode = false; }
            finally { this.seoBothLoading = false; }
        },

        async applyTitle() {
            if (!this.selectedProductForSeo?.id || !this.seoResult?.title || this.titleApplying) return;
            this.titleApplying = true;
            try {
                const res = await fetch(`/api/marketplace/products/${this.selectedProductForSeo.id}`, {
                    method: 'PUT', headers: this.getHeaders(), credentials: 'include',
                    body: JSON.stringify({ title: this.seoResult.title }),
                });
                if (!res.ok) throw new Error('Ошибка обновления');
                if (this.products) {
                    const idx = this.products.findIndex(p => p.id === this.selectedProductForSeo.id);
                    if (idx !== -1) { this.products[idx].title = this.seoResult.title; if (typeof this.applyFilter === 'function') this.applyFilter(); }
                }
                this.titleApplied = true;
                setTimeout(() => { this.titleApplied = false; }, 3000);
            } catch (e) { alert(e.message || 'Ошибка применения'); }
            finally { this.titleApplying = false; }
        },

        copySeoField(text, key) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                this.seocopied = key;
                setTimeout(() => { if (this.seocopied === key) this.seocopied = null; }, 2000);
            });
        },
    };
}
</script>
@endpushonce
