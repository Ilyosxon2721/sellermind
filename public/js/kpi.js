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
        marketplaceAccounts: [],
        calculating: false,
        chartData: { labels: [], achievements: [], bonuses: [] },
        chart: null,

        // Модалки
        showPlanModal: false,
        showSphereModal: false,
        showScaleModal: false,
        showActualsModal: false,
        actualsForm: { id: null, actual_revenue: 0, actual_margin: 0, actual_orders: 0, employee_name: '', sphere_name: '' },

        // Формы
        planForm: { id: null, employee_id: '', kpi_sales_sphere_id: '', kpi_bonus_scale_id: '', period_year: now.getFullYear(), period_month: now.getMonth()+1, target_revenue: 0, target_margin: 0, target_orders: 0, weight_revenue: 40, weight_margin: 40, weight_orders: 20 },
        aiSuggesting: false,
        aiReasoning: '',
        sphereForm: { id: null, name: '', description: '', color: '#3B82F6', icon: '📊', marketplace_account_ids: [], offline_sale_types: [], is_active: true },
        scaleForm: { id: null, name: '', is_default: false, tiers: [] },

        // Уведомления
        notification: { show: false, message: '', type: 'success' },

        init() {
            this.loadDashboard();
            this.loadSpheres();
            this.loadScales();
            this.loadEmployees();
            this.loadMarketplaceAccounts();
            this.loadChartData(6);
        },

        reloadCurrentTab() {
            if (this.tab === 'dashboard') { this.loadDashboard(); this.loadChartData(); }
            else if (this.tab === 'plans') this.loadPlans();
        },

        emptyPlanForm() {
            return { id: null, employee_id: '', kpi_sales_sphere_id: '', kpi_bonus_scale_id: '', period_year: new Date().getFullYear(), period_month: new Date().getMonth()+1, target_revenue: 0, target_margin: 0, target_orders: 0, weight_revenue: 40, weight_margin: 40, weight_orders: 20 };
        },
        emptySphereForm() {
            return { id: null, name: '', description: '', color: '#3B82F6', icon: '📊', marketplace_account_ids: [], offline_sale_types: [], is_active: true };
        },
        emptyScaleForm() {
            return { id: null, name: '', is_default: false, tiers: [] };
        },

        getToken() {
            var t = localStorage.getItem('_x_auth_token');
            if (t) try { return JSON.parse(t); } catch(e) { return t; }
            return null;
        },

        async api(url, method, body) {
            method = method || 'GET';
            body = body || null;
            var token = this.getToken();
            var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            if (token) headers['Authorization'] = 'Bearer ' + token;
            var opts = { method: method, credentials: 'include', headers: headers };
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
                var res = await this.api('finance/kpi/dashboard?year=' + this.year + '&month=' + this.month);
                this.dashboard = res.data ?? res;
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadPlans() {
            try {
                var res = await this.api('finance/kpi/plans?year=' + this.year + '&month=' + this.month);
                this.plans = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadSpheres() {
            try {
                var res = await this.api('finance/kpi/spheres');
                this.spheres = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadScales() {
            try {
                var res = await this.api('finance/kpi/bonus-scales');
                this.scales = res.data ?? [];
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadEmployees() {
            try {
                var res = await this.api('finance/employees');
                this.employees = res.data ?? [];
            } catch (e) { /* сотрудники могут быть не доступны */ }
        },
        async loadMarketplaceAccounts() {
            try {
                var res = await this.api('finance/kpi/marketplace-accounts');
                this.marketplaceAccounts = res.data ?? [];
            } catch (e) { /* маркетплейсы могут быть не доступны */ }
        },
        async loadChartData(months) {
            months = months || 6;
            try {
                var res = await this.api('finance/kpi/chart-data?months=' + months);
                this.chartData = res.data ?? res;
                this.renderChart();
            } catch (e) { console.error('Chart load error:', e); }
        },

        // Расчёт KPI
        async calculateAll() {
            this.calculating = true;
            try {
                await this.api('finance/kpi/plans/calculate', 'POST', { year: parseInt(this.year), month: parseInt(this.month) });
                this.notify('KPI рассчитаны');
                await this.loadDashboard();
                if (this.tab === 'plans') await this.loadPlans();
            } catch (e) { this.notify(e.message, 'error'); }
            this.calculating = false;
        },

        // CRUD Планы
        openPlanModal() {
            this.planForm = this.emptyPlanForm();
            this.aiReasoning = '';
            this.showPlanModal = true;
        },
        editPlan(p) {
            this.planForm = {
                id: p.id,
                employee_id: p.employee_id,
                kpi_sales_sphere_id: p.kpi_sales_sphere_id,
                kpi_bonus_scale_id: p.kpi_bonus_scale_id,
                period_year: p.period_year,
                period_month: p.period_month,
                target_revenue: p.target_revenue,
                target_margin: p.target_margin,
                target_orders: p.target_orders,
                weight_revenue: p.weight_revenue,
                weight_margin: p.weight_margin,
                weight_orders: p.weight_orders,
                notes: p.notes || ''
            };
            this.aiReasoning = '';
            this.showPlanModal = true;
        },
        openActualsModal(p) {
            this.actualsForm = {
                id: p.id,
                actual_revenue: p.actual_revenue || 0,
                actual_margin: p.actual_margin || 0,
                actual_orders: p.actual_orders || 0,
                employee_name: p.employee?.name ?? '—',
                sphere_name: p.sales_sphere?.name ?? '—'
            };
            this.showActualsModal = true;
        },
        async saveActuals() {
            try {
                await this.api('finance/kpi/plans/' + this.actualsForm.id + '/actuals', 'PUT', {
                    actual_revenue: parseFloat(this.actualsForm.actual_revenue) || 0,
                    actual_margin: parseFloat(this.actualsForm.actual_margin) || 0,
                    actual_orders: parseInt(this.actualsForm.actual_orders) || 0
                });
                this.showActualsModal = false;
                this.notify('Фактические данные сохранены');
                await this.loadPlans();
                await this.loadDashboard();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async aiSuggestPlan() {
            if (!this.planForm.employee_id || !this.planForm.kpi_sales_sphere_id) {
                this.notify('Выберите сотрудника и сферу продаж', 'error');
                return;
            }
            this.aiSuggesting = true;
            this.aiReasoning = '';
            try {
                var res = await this.api('finance/kpi/plans/ai-suggest', 'POST', {
                    employee_id: parseInt(this.planForm.employee_id),
                    kpi_sales_sphere_id: parseInt(this.planForm.kpi_sales_sphere_id),
                    period_year: parseInt(this.planForm.period_year),
                    period_month: parseInt(this.planForm.period_month)
                });
                var data = res.data ?? res;
                this.planForm.target_revenue = data.target_revenue ?? 0;
                this.planForm.target_margin = data.target_margin ?? 0;
                this.planForm.target_orders = data.target_orders ?? 0;
                this.planForm.weight_revenue = data.weight_revenue ?? 40;
                this.planForm.weight_margin = data.weight_margin ?? 40;
                this.planForm.weight_orders = data.weight_orders ?? 20;
                this.aiReasoning = data.reasoning ?? '';
                this.notify('ИИ-рекомендация получена');
            } catch (e) { this.notify(e.message, 'error'); }
            this.aiSuggesting = false;
        },
        async savePlan() {
            // Валидация суммы весов
            var wSum = parseInt(this.planForm.weight_revenue || 0) + parseInt(this.planForm.weight_margin || 0) + parseInt(this.planForm.weight_orders || 0);
            if (wSum !== 100) {
                this.notify('Сумма весов должна быть 100 (сейчас: ' + wSum + ')', 'error');
                return;
            }
            try {
                if (this.planForm.id) {
                    await this.api('finance/kpi/plans/' + this.planForm.id, 'PUT', this.planForm);
                } else {
                    await this.api('finance/kpi/plans', 'POST', this.planForm);
                }
                this.showPlanModal = false;
                this.notify('План сохранён');
                await this.loadPlans();
                await this.loadDashboard();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async approvePlan(id) {
            try {
                await this.api('finance/kpi/plans/' + id + '/approve', 'POST');
                this.notify('План утверждён');
                await this.loadPlans();
                await this.loadDashboard();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deletePlan(id) {
            if (!confirm('Удалить план?')) return;
            try {
                await this.api('finance/kpi/plans/' + id, 'DELETE');
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
            this.sphereForm = Object.assign({}, s, {
                marketplace_account_ids: (s.marketplace_account_ids || []).map(function(id) { return parseInt(id); }),
                offline_sale_types: (s.offline_sale_types || []).slice()
            });
            this.showSphereModal = true;
        },
        toggleMarketplace(id) {
            var ids = this.sphereForm.marketplace_account_ids || [];
            var idx = ids.indexOf(id);
            if (idx === -1) {
                ids.push(id);
            } else {
                ids.splice(idx, 1);
            }
            this.sphereForm.marketplace_account_ids = ids.slice();
        },
        toggleOfflineSaleType(type) {
            var types = this.sphereForm.offline_sale_types || [];
            var idx = types.indexOf(type);
            if (idx === -1) {
                types.push(type);
            } else {
                types.splice(idx, 1);
            }
            this.sphereForm.offline_sale_types = types.slice();
        },
        async saveSphere() {
            try {
                if (this.sphereForm.id) {
                    await this.api('finance/kpi/spheres/' + this.sphereForm.id, 'PUT', this.sphereForm);
                } else {
                    await this.api('finance/kpi/spheres', 'POST', this.sphereForm);
                }
                this.showSphereModal = false;
                this.notify('Сфера сохранена');
                await this.loadSpheres();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deleteSphere(id) {
            if (!confirm('Удалить сферу?')) return;
            try {
                await this.api('finance/kpi/spheres/' + id, 'DELETE');
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
                    await this.api('finance/kpi/bonus-scales/' + this.scaleForm.id, 'PUT', this.scaleForm);
                } else {
                    await this.api('finance/kpi/bonus-scales', 'POST', this.scaleForm);
                }
                this.showScaleModal = false;
                this.notify('Шкала сохранена');
                await this.loadScales();
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deleteScale(id) {
            if (!confirm('Удалить шкалу?')) return;
            try {
                await this.api('finance/kpi/bonus-scales/' + id, 'DELETE');
                this.notify('Шкала удалена');
                await this.loadScales();
            } catch (e) { this.notify(e.message, 'error'); }
        },

        // График KPI
        renderChart() {
            if (!window.Chart) {
                console.warn('Chart.js не загружен');
                return;
            }
            var canvas = document.getElementById('kpiChart');
            if (!canvas) return;

            var ctx = canvas.getContext('2d');

            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.chartData.labels || [],
                    datasets: [
                        {
                            label: 'Средний % выполнения',
                            data: this.chartData.achievements || [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y',
                            fill: true,
                        },
                        {
                            label: 'Бонусы (сум)',
                            data: this.chartData.bonuses || [],
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y1',
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        if (context.datasetIndex === 0) {
                                            label += context.parsed.y.toFixed(1) + '%';
                                        } else {
                                            label += context.parsed.y.toLocaleString('ru-RU') + ' сум';
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: '% выполнения' },
                            ticks: { callback: function(v) { return v + '%'; } }
                        },
                        y1: {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'Бонусы (сум)' },
                            grid: { drawOnChartArea: false },
                            ticks: { callback: function(v) { return v.toLocaleString('ru-RU'); } }
                        },
                    }
                }
            });
        },
    };
}
