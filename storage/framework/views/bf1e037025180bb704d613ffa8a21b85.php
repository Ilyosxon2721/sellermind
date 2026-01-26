<?php $__env->startSection('content'); ?>

<div x-data="marketplaceDashboard()" class="browser-only flex h-screen bg-gray-50">

    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-gray-600 text-sm">KPI by marketplaces</p>
                </div>
                <a href="/marketplace" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Back to Accounts
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select x-model="filters.period" @change="loadData" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="today">Today</option>
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marketplace</label>
                        <select x-model="filters.marketplace" @change="loadData" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All</option>
                            <option value="wb">Wildberries</option>
                            <option value="ozon">Ozon</option>
                            <option value="uzum">Uzum Market</option>
                            <option value="ym">Yandex Market</option>
                        </select>
                    </div>

                    <template x-if="filters.period === 'custom'">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                            <input type="date" x-model="filters.date_from" @change="loadData" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </template>

                    <template x-if="filters.period === 'custom'">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                            <input type="date" x-model="filters.date_to" @change="loadData" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </template>

                    <button @click="loadData" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
                        <span x-show="!loading">Refresh</span>
                        <span x-show="loading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading && !data" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Agent Task Success Message -->
            <div x-show="agentTaskMessage" x-transition class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                <span x-text="agentTaskMessage"></span>
            </div>

            <div x-show="data">
                <!-- Overall KPI -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Overall Summary</h2>
                        <span class="text-sm text-gray-500" x-text="periodLabel"></span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <!-- Revenue -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-1">Revenue</div>
                            <div class="text-2xl font-bold text-gray-900" x-text="formatNumber(data?.overall_kpi?.revenue || 0)"></div>
                        </div>

                        <!-- Orders -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-1">Orders</div>
                            <div class="text-2xl font-bold text-gray-900" x-text="data?.overall_kpi?.orders_count || 0"></div>
                        </div>

                        <!-- Average Check -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-1">Avg Check</div>
                            <div class="text-2xl font-bold text-gray-900" x-text="formatNumber(data?.overall_kpi?.avg_check || 0)"></div>
                        </div>

                        <!-- Return Rate -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-1">Return Rate</div>
                            <div class="text-2xl font-bold" :class="(data?.overall_kpi?.return_rate || 0) > 5 ? 'text-red-600' : 'text-green-600'" x-text="(data?.overall_kpi?.return_rate || 0).toFixed(1) + '%'"></div>
                        </div>

                        <!-- Active SKUs -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-1">Active SKUs</div>
                            <div class="text-2xl font-bold text-gray-900" x-text="data?.overall_kpi?.active_skus || 0"></div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Charts</h2>
                        <span class="text-sm text-gray-500">Revenue and orders by day</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Revenue Chart -->
                        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-3">Revenue by day</div>
                            <div class="relative" style="height: 250px;">
                                <canvas id="chartRevenue"></canvas>
                            </div>
                        </div>

                        <!-- Orders Chart -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="text-sm text-gray-500 mb-3">Orders by day</div>
                            <div class="relative" style="height: 250px;">
                                <canvas id="chartOrders"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- By Account Table -->
                <div class="bg-white rounded-xl border border-gray-200 mb-6">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">By Marketplace</h2>
                    </div>

                    <div x-show="!data?.accounts?.length" class="p-8 text-center text-gray-500">
                        No connected marketplace accounts.
                    </div>

                    <div x-show="data?.accounts?.length" class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marketplace</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Check</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Return Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Sync</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="account in data.accounts" :key="account.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs"
                                                     :class="getMarketplaceColor(account.marketplace)">
                                                    <span x-text="account.marketplace.toUpperCase().substring(0, 2)"></span>
                                                </div>
                                                <span class="font-medium" x-text="account.marketplace_label"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600" x-text="account.name || '-'"></td>
                                        <td class="px-4 py-3 text-right font-medium" x-text="formatNumber(getAccountKpi(account.id)?.revenue || 0)"></td>
                                        <td class="px-4 py-3 text-right" x-text="getAccountKpi(account.id)?.orders_count || 0"></td>
                                        <td class="px-4 py-3 text-right" x-text="formatNumber(getAccountKpi(account.id)?.avg_check || 0)"></td>
                                        <td class="px-4 py-3 text-right">
                                            <span :class="(getAccountKpi(account.id)?.return_rate || 0) > 5 ? 'text-red-600' : 'text-green-600'" x-text="(getAccountKpi(account.id)?.return_rate || 0).toFixed(1) + '%'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="formatSyncDate(getAccountKpi(account.id)?.last_sync_at)"></td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="getSyncStatusClass(getAccountKpi(account.id)?.last_sync_status)"
                                                  x-text="getAccountKpi(account.id)?.last_sync_status || 'no data'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Problem SKUs & Recent Events -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Problem SKUs -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Problem SKUs (Returns / Low Revenue)</h2>
                        </div>

                        <div x-show="!data?.problem_skus?.length" class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-gray-600">No problem SKUs for the selected period.</p>
                        </div>

                        <div x-show="data?.problem_skus?.length" class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Return Rate</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="(sku, index) in data.problem_skus.slice(0, 10)" :key="index">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-mono text-sm" x-text="sku.external_offer_id || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" x-text="sku.name || '-'"></td>
                                            <td class="px-4 py-3 text-right" x-text="sku.total_qty"></td>
                                            <td class="px-4 py-3 text-right" x-text="formatNumber(sku.total_revenue)"></td>
                                            <td class="px-4 py-3 text-right">
                                                <span :class="sku.return_rate > 10 ? 'text-red-600 font-medium' : (sku.return_rate > 5 ? 'text-yellow-600' : 'text-gray-600')" x-text="sku.return_rate.toFixed(1) + '%'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    @click="createAgentTask(sku)"
                                                    :disabled="creatingAgentTask"
                                                    class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 disabled:opacity-50 transition">
                                                    <span x-show="!creatingAgentTask">Agent</span>
                                                    <span x-show="creatingAgentTask" class="flex items-center">
                                                        <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                    </span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Events</h2>
                        </div>

                        <div x-show="!data?.recent_events?.length" class="p-8 text-center text-gray-500">
                            No sync events yet.
                        </div>

                        <div x-show="data?.recent_events?.length" class="divide-y divide-gray-200 max-h-[400px] overflow-y-auto">
                            <template x-for="(event, index) in data.recent_events.slice(0, 15)" :key="index">
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs text-gray-500 mb-1" x-text="formatEventDate(event.created_at)"></div>
                                            <div class="font-medium text-sm text-gray-900 capitalize" x-text="event.type"></div>
                                            <div x-show="event.message" class="text-xs text-gray-500 mt-1 truncate" x-text="event.message"></div>
                                        </div>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full flex-shrink-0"
                                              :class="getSyncStatusClass(event.status)"
                                              x-text="event.status"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function marketplaceDashboard() {
    return {
        loading: false,
        data: null,
        chartsData: null,
        revenueChart: null,
        ordersChart: null,
        creatingAgentTask: false,
        agentTaskMessage: '',
        filters: {
            period: '7d',
            marketplace: 'all',
            date_from: '',
            date_to: ''
        },

        async init() {
            if (!this.$store.auth.isAuthenticated) {
                window.location.href = '/login';
                return;
            }
            await this.loadData();
        },

        get periodLabel() {
            if (!this.data?.period) return '';
            const from = new Date(this.data.period.from);
            const to = new Date(this.data.period.to);
            return `${from.toLocaleDateString('ru-RU')} - ${to.toLocaleDateString('ru-RU')}`;
        },

        getAuthToken() {
            let token = localStorage.getItem('_x_auth_token');
            if (token) {
                try {
                    return JSON.parse(token);
                } catch (e) {
                    return token;
                }
            }
            return localStorage.getItem('auth_token');
        },

        async loadData() {
            if (!this.$store.auth.currentCompany) {
                return;
            }

            this.loading = true;
            try {
                const params = new URLSearchParams({
                    company_id: this.$store.auth.currentCompany.id,
                    period: this.filters.period,
                    marketplace: this.filters.marketplace
                });

                if (this.filters.period === 'custom') {
                    if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.append('date_to', this.filters.date_to);
                }

                const [statsRes, chartsRes] = await Promise.all([
                    fetch(`/api/marketplace/dashboard/stats?${params}`, {
                        headers: {
                            'Authorization': 'Bearer ' + this.getAuthToken(),
                            'Accept': 'application/json'
                        }
                    }),
                    fetch(`/api/marketplace/dashboard/charts-data?${params}`, {
                        headers: {
                            'Authorization': 'Bearer ' + this.getAuthToken(),
                            'Accept': 'application/json'
                        }
                    })
                ]);

                if (statsRes.ok) {
                    this.data = await statsRes.json();
                } else if (statsRes.status === 401) {
                    window.location.href = '/login';
                    return;
                }

                if (chartsRes.ok) {
                    const chartsJson = await chartsRes.json();
                    if (chartsJson.success) {
                        this.chartsData = chartsJson.data;
                        this.$nextTick(() => this.renderCharts());
                    }
                }
            } catch (e) {
                console.error('Error loading dashboard:', e);
            } finally {
                this.loading = false;
            }
        },

        renderCharts() {
            if (!this.chartsData) return;

            const labels = this.chartsData.labels || [];
            const overall = this.chartsData.overall || { revenue: [], orders: [] };
            const byMarketplace = this.chartsData.by_marketplace || {};

            // Destroy existing charts
            if (this.revenueChart) {
                this.revenueChart.destroy();
            }
            if (this.ordersChart) {
                this.ordersChart.destroy();
            }

            const revenueCtx = document.getElementById('chartRevenue');
            const ordersCtx = document.getElementById('chartOrders');

            if (!revenueCtx || !ordersCtx) return;

            // Revenue Chart
            const revenueDatasets = [
                {
                    label: 'All Marketplaces',
                    data: overall.revenue || [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ];

            // Add marketplace-specific lines
            const mpColors = {
                'wb': { border: 'rgb(147, 51, 234)', bg: 'rgba(147, 51, 234, 0.1)' },
                'ozon': { border: 'rgb(14, 165, 233)', bg: 'rgba(14, 165, 233, 0.1)' },
                'uzum': { border: 'rgb(34, 197, 94)', bg: 'rgba(34, 197, 94, 0.1)' },
                'ym': { border: 'rgb(245, 158, 11)', bg: 'rgba(245, 158, 11, 0.1)' }
            };

            Object.keys(byMarketplace).forEach(mp => {
                const mpData = byMarketplace[mp];
                if (mpData && mpData.revenue) {
                    const hasData = mpData.revenue.some(v => v > 0);
                    if (hasData) {
                        revenueDatasets.push({
                            label: this.mpName(mp),
                            data: mpData.revenue,
                            borderColor: mpColors[mp]?.border || 'rgb(156, 163, 175)',
                            borderWidth: 1,
                            borderDash: [4, 3],
                            tension: 0.3,
                            fill: false
                        });
                    }
                }
            });

            this.revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: labels.map(d => this.formatChartDate(d)),
                    datasets: revenueDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 6 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => this.formatNumber(value)
                            }
                        }
                    }
                }
            });

            // Orders Chart
            this.ordersChart = new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: labels.map(d => this.formatChartDate(d)),
                    datasets: [{
                        label: 'Orders',
                        data: overall.orders || [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        },

        mpName(key) {
            const names = {
                'wb': 'Wildberries',
                'ozon': 'Ozon',
                'uzum': 'Uzum',
                'ym': 'Yandex Market'
            };
            return names[key] || key;
        },

        formatChartDate(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            return `${parts[2]}.${parts[1]}`;
        },

        async createAgentTask(sku) {
            if (!sku.account_id) {
                alert('No account linked to this SKU');
                return;
            }

            this.creatingAgentTask = true;
            this.agentTaskMessage = '';

            try {
                // TODO: Find the appropriate agent for SKU analysis
                // For now, we'll create a task using the existing agent infrastructure
                const res = await fetch('/api/agent/tasks', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.getAuthToken(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        agent_id: 1, // TODO: Get appropriate agent ID for SKU analysis
                        title: `SKU Analysis: ${sku.external_offer_id}`,
                        description: `Analyze problem SKU ${sku.external_offer_id} (${sku.name || 'N/A'}) with return rate ${sku.return_rate.toFixed(1)}%`,
                        company_id: this.$store.auth.currentCompany?.id,
                        type: 'marketplace_sku_analysis',
                        input_payload: {
                            external_offer_id: sku.external_offer_id,
                            product_name: sku.name,
                            account_id: sku.account_id,
                            total_qty: sku.total_qty,
                            total_revenue: sku.total_revenue,
                            return_rate: sku.return_rate
                        }
                    })
                });

                if (res.ok) {
                    const result = await res.json();
                    this.agentTaskMessage = `Agent task created: #${result.task?.id}`;
                    setTimeout(() => { this.agentTaskMessage = ''; }, 5000);
                } else {
                    const err = await res.json();
                    alert(err.message || 'Failed to create agent task');
                }
            } catch (e) {
                console.error('Error creating agent task:', e);
                alert('Error creating agent task');
            } finally {
                this.creatingAgentTask = false;
            }
        },

        getAccountKpi(accountId) {
            return this.data?.by_account?.[accountId] || null;
        },

        formatNumber(num) {
            if (!num) return '0';
            return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(num);
        },

        formatSyncDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },

        formatEventDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },

        getMarketplaceColor(marketplace) {
            const colors = {
                'wb': 'bg-gradient-to-br from-purple-500 to-purple-700',
                'ozon': 'bg-gradient-to-br from-blue-500 to-blue-700',
                'uzum': 'bg-gradient-to-br from-green-500 to-green-700',
                'ym': 'bg-gradient-to-br from-yellow-500 to-orange-500'
            };
            return colors[marketplace] || 'bg-gray-500';
        },

        getSyncStatusClass(status) {
            const classes = {
                'success': 'bg-green-100 text-green-800',
                'error': 'bg-red-100 text-red-800',
                'running': 'bg-blue-100 text-blue-800',
                'pending': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-600';
        }
    };
}
</script>


<div class="pwa-only min-h-screen" x-data="marketplaceDashboard()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Dashboard','backUrl' => '/marketplace']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Dashboard','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/marketplace')]); ?>
        <button @click="loadData()" :disabled="loading" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $attributes = $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $component = $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadData">

        
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Период</label>
                    <select class="native-input mt-1" x-model="filters.period" @change="loadData()">
                        <option value="today">Сегодня</option>
                        <option value="7d">7 дней</option>
                        <option value="30d">30 дней</option>
                    </select>
                </div>
                <div>
                    <label class="native-caption">Маркетплейс</label>
                    <select class="native-input mt-1" x-model="filters.marketplace" @change="loadData()">
                        <option value="all">Все</option>
                        <option value="wb">Wildberries</option>
                        <option value="ozon">Ozon</option>
                        <option value="uzum">Uzum</option>
                        <option value="ym">Yandex Market</option>
                    </select>
                </div>
            </div>
        </div>

        
        <div x-show="loading && !data" class="px-4">
            <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 3]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $attributes = $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $component = $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
        </div>

        
        <div x-show="data" class="px-4 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="native-card text-center py-4">
                    <p class="text-xl font-bold text-gray-900" x-text="formatNumber(data?.overall_kpi?.revenue || 0)"></p>
                    <p class="native-caption">Выручка</p>
                </div>
                <div class="native-card text-center py-4">
                    <p class="text-xl font-bold text-gray-900" x-text="data?.overall_kpi?.orders_count || 0"></p>
                    <p class="native-caption">Заказов</p>
                </div>
                <div class="native-card text-center py-4">
                    <p class="text-xl font-bold text-gray-900" x-text="formatNumber(data?.overall_kpi?.avg_check || 0)"></p>
                    <p class="native-caption">Средний чек</p>
                </div>
                <div class="native-card text-center py-4">
                    <p class="text-xl font-bold" :class="(data?.overall_kpi?.return_rate || 0) > 5 ? 'text-red-600' : 'text-green-600'" x-text="(data?.overall_kpi?.return_rate || 0).toFixed(1) + '%'"></p>
                    <p class="native-caption">Возвраты</p>
                </div>
            </div>

            
            <div x-show="data?.accounts?.length" class="native-card">
                <p class="native-body font-semibold mb-3">По маркетплейсам</p>
                <div class="space-y-2">
                    <template x-for="account in data.accounts" :key="account.id">
                        <div class="p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="native-body font-semibold" x-text="account.marketplace_label"></span>
                                <span class="native-body font-bold" x-text="formatNumber(getAccountKpi(account.id)?.revenue || 0)"></span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="native-caption" x-text="(getAccountKpi(account.id)?.orders_count || 0) + ' заказов'"></span>
                                <span class="native-caption" :class="(getAccountKpi(account.id)?.return_rate || 0) > 5 ? 'text-red-600' : ''" x-text="(getAccountKpi(account.id)?.return_rate || 0).toFixed(1) + '% возврат'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\marketplace\dashboard.blade.php ENDPATH**/ ?>