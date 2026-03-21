function kpiPage(config) {
    const now = new Date();
    return {
        tab: 'dashboard',
        year: now.getFullYear(),
        month: now.getMonth() + 1,
        years: Array.from({length: 5}, (_, i) => now.getFullYear() - 2 + i),
        tabLabels: config.tabLabels,

        // Данные
        dashboard: {},
        plans: [],
        spheres: [],
        scales: [],
        employees: [],
        calculating: false,

        // Модалки
        showPlanModal: false,
        showSphereModal: false,
        showScaleModal: false,

        // Формы
        planForm: { id: null, employee_id: '', kpi_sales_sphere_id: '', kpi_bonus_scale_id: '', period_year: now.getFullYear(), period_month: now.getMonth()+1, target_revenue: 0, target_margin: 0, target_orders: 0, weight_revenue: 40, weight_margin: 40, weight_orders: 20 },
        sphereForm: { id: null, name: '', description: '', color: '#3B82F6', icon: '📊', is_active: true },
        scaleForm: { id: null, name: '', is_default: false, tiers: [] },

        // Уведомления
        notification: { show: false, message: '', type: 'success' },

        init() {
            this.loadDashboard();
            this.loadSpheres();
            this.loadScales();
            this.loadEmployees();
        },

        emptyPlanForm() {
            return { id: null, employee_id: '', kpi_sales_sphere_id: '', kpi_bonus_scale_id: '', period_year: new Date().getFullYear(), period_month: new Date().getMonth()+1, target_revenue: 0, target_margin: 0, target_orders: 0, weight_revenue: 40, weight_margin: 40, weight_orders: 20 };
        },
        emptySphereForm() {
            return { id: null, name: '', description: '', color: '#3B82F6', icon: '📊', is_active: true };
        },
        emptyScaleForm() {
            return { id: null, name: '', is_default: false, tiers: [] };
        },

        async api(url, method, body) {
            method = method || 'GET';
            body = body || null;
            var opts = { method: method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } };
            if (body) opts.body = JSON.stringify(body);
            var res = await fetch('/api/' + url, opts);
            if (!res.ok) {
                var err = await res.json().catch(function() { return {}; });
                throw new Error(err.message || 'Ошибка запроса');
            }
            return res.json();
        },

        notify(message, type) {
            type = type || 'success';
            this.notification = { show: true, message: message, type: type };
            var self = this;
            setTimeout(function() { self.notification.show = false; }, 4000);
        },

        fmt(n) {
            return (n ?? 0).toLocaleString('ru-RU', { maximumFractionDigits: 0 });
        },

        monthName(m) {
            return ['', 'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'][m] || '';
        },

        statusLabel(s) {
            return (config.statuses || {})[s] || s;
        },

        statusClass(s) {
            return { active: 'bg-gray-100 text-gray-700', calculated: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-700' }[s] || 'bg-gray-100 text-gray-700';
        },

        bonusTypeLabel(t) {
            return (config.bonusTypes || {})[t] || t;
        },

        // Загрузка данных
        async loadDashboard() {
            try {
                var res = await this.api('kpi/dashboard?year=' + this.year + '&month=' + this.month);
                this.dashboard = res.data ?? res;
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadPlans() {
            try {
                var res = await this.api('kpi/plans?year=' + this.year + '&month=' + this.month);
                this.plans = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadSpheres() {
            try {
                var res = await this.api('kpi/spheres');
                this.spheres = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadScales() {
            try {
                var res = await this.api('kpi/bonus-scales');
                this.scales = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadEmployees() {
            try {
                var res = await this.api('employees');
                this.employees = res.data ?? [];
            } catch (e) { /* сотрудники могут быть не доступны */ }
        },

        // Расчёт KPI
        async calculateAll() {
            this.calculating = true;
            try {
                await this.api('kpi/plans/calculate', 'POST', { year: parseInt(this.year), month: parseInt(this.month) });
                this.notify('KPI рассчитаны');
                await this.loadDashboard();
                if (this.tab === 'plans') await this.loadPlans();
            } catch (e) { this.notify(e.message, 'error'); }
            this.calculating = false;
        },

        // CRUD Планы
        openPlanModal() {
            this.planForm = this.emptyPlanForm();
            this.showPlanModal = true;
        },
        async savePlan() {
            try {
                if (this.planForm.id) {
                    await this.api('kpi/plans/' + this.planForm.id, 'PUT', this.planForm);
                } else {
                    await this.api('kpi/plans', 'POST', this.planForm);
                }
                this.showPlanModal = false;
                this.notify('План сохранён');
                await this.loadPlans();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async approvePlan(id) {
            try {
                await this.api('kpi/plans/' + id + '/approve', 'POST');
                this.notify('План утверждён');
                await this.loadPlans();
                await this.loadDashboard();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deletePlan(id) {
            if (!confirm('Удалить план?')) return;
            try {
                await this.api('kpi/plans/' + id, 'DELETE');
                this.notify('План удалён');
                await this.loadPlans();
            } catch (e) { this.notify(e.message, 'error'); }
        },

        // CRUD Сферы
        openSphereModal() {
            this.sphereForm = this.emptySphereForm();
            this.showSphereModal = true;
        },
        editSphere(s) {
            this.sphereForm = Object.assign({}, s);
            this.showSphereModal = true;
        },
        async saveSphere() {
            try {
                if (this.sphereForm.id) {
                    await this.api('kpi/spheres/' + this.sphereForm.id, 'PUT', this.sphereForm);
                } else {
                    await this.api('kpi/spheres', 'POST', this.sphereForm);
                }
                this.showSphereModal = false;
                this.notify('Сфера сохранена');
                await this.loadSpheres();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deleteSphere(id) {
            if (!confirm('Удалить сферу?')) return;
            try {
                await this.api('kpi/spheres/' + id, 'DELETE');
                this.notify('Сфера удалена');
                await this.loadSpheres();
            } catch (e) { this.notify(e.message, 'error'); }
        },

        // CRUD Шкалы
        openScaleModal() {
            this.scaleForm = this.emptyScaleForm();
            this.showScaleModal = true;
        },
        editScale(sc) {
            this.scaleForm = Object.assign({}, sc, { tiers: (sc.tiers ?? []).map(function(t) { return Object.assign({}, t); }) });
            this.showScaleModal = true;
        },
        addTier() {
            this.scaleForm.tiers.push({ min_percent: 0, max_percent: null, bonus_type: 'fixed', bonus_value: 0 });
        },
        async saveScale() {
            try {
                if (this.scaleForm.id) {
                    await this.api('kpi/bonus-scales/' + this.scaleForm.id, 'PUT', this.scaleForm);
                } else {
                    await this.api('kpi/bonus-scales', 'POST', this.scaleForm);
                }
                this.showScaleModal = false;
                this.notify('Шкала сохранена');
                await this.loadScales();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deleteScale(id) {
            if (!confirm('Удалить шкалу?')) return;
            try {
                await this.api('kpi/bonus-scales/' + id, 'DELETE');
                this.notify('Шкала удалена');
                await this.loadScales();
            } catch (e) { this.notify(e.message, 'error'); }
        },
    };
}
