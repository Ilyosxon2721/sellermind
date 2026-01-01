<div x-data="accessTab()">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Управление доступами</h2>
        <p class="text-sm text-gray-500 mt-1">Настройка прав доступа сотрудников к различным разделам системы</p>
    </div>

    <!-- Company Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label">Выберите компанию</label>
            <select class="form-select" x-model="selectedCompanyId" @change="loadEmployees()">
                <option value="">Выберите компанию...</option>
                <template x-for="company in companies" :key="company.id">
                    <option :value="company.id" x-text="company.name"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Access Matrix -->
    <div x-show="selectedCompanyId">
        <template x-if="loading">
            <div class="card">
                <div class="card-body text-center py-12">
                    <div class="spinner mx-auto"></div>
                    <p class="text-gray-500 mt-2">Загрузка...</p>
                </div>
            </div>
        </template>

        <div x-show="!loading && employees.length > 0" class="card">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-white">
                                    Сотрудник
                                </th>
                                <template x-for="section in sections" :key="section.key">
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        x-text="section.name">
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="employee in employees" :key="employee.id">
                                <tr :class="employee.pivot.role === 'owner' ? 'bg-blue-50' : ''">
                                    <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-medium text-sm mr-3"
                                                 x-text="employee.name.charAt(0).toUpperCase()">
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900" x-text="employee.name"></div>
                                                <div class="text-xs text-gray-500" x-text="employee.email"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <template x-for="section in sections" :key="section.key">
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="checkbox"
                                                class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                                :checked="hasAccess(employee.id, section.key) || employee.pivot.role === 'owner'"
                                                :disabled="employee.pivot.role === 'owner' || !canManageAccess()"
                                                @change="toggleAccess(employee.id, section.key, $event.target.checked)">
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>Примечание:</strong> Владельцы компании имеют полный доступ ко всем разделам по умолчанию.
                    </p>
                </div>
            </div>
        </div>

        <template x-if="!loading && employees.length === 0">
            <div class="card">
                <div class="card-body text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <p class="text-gray-900 font-medium mb-1">Нет сотрудников</p>
                    <p class="text-gray-500 text-sm">Добавьте сотрудников на вкладке "Сотрудники"</p>
                </div>
            </div>
        </template>
    </div>

    <!-- No Company Selected -->
    <div x-show="!selectedCompanyId && !loading" class="card">
        <div class="card-body text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500">Выберите компанию для управления доступами</p>
        </div>
    </div>
</div>

<script>
function accessTab() {
    return {
        companies: [],
        selectedCompanyId: '',
        employees: [],
        accessRights: {},
        loading: false,
        sections: [
            { key: 'marketplace', name: 'Маркетплейсы' },
            { key: 'warehouse', name: 'Склад' },
            { key: 'sales', name: 'Продажи' },
            { key: 'inventory', name: 'Инвентаризация' },
            { key: 'counterparties', name: 'Контрагенты' },
            { key: 'pricing', name: 'Цены' },
            { key: 'finance', name: 'Финансы' },
            { key: 'logs', name: 'Логи' },
            { key: 'analytics', name: 'Аналитика' }
        ],

        async init() {
            await this.loadCompanies();
            this.loadAccessRights();
        },

        async loadCompanies() {
            try {
                const response = await fetch('/api/companies', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.companies = data.companies || data.data || [];

                    if (this.companies.length > 0) {
                        this.selectedCompanyId = this.companies[0].id;
                        await this.loadEmployees();
                    }
                }
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },

        async loadEmployees() {
            if (!this.selectedCompanyId) {
                this.employees = [];
                return;
            }

            this.loading = true;
            try {
                // Note: This endpoint needs to be created
                const response = await fetch(`/api/companies/${this.selectedCompanyId}/members`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.employees = data.members || data.data || [];
                } else {
                    this.employees = [];
                }
            } catch (error) {
                console.error('Error loading employees:', error);
                this.employees = [];
            } finally {
                this.loading = false;
            }
        },

        loadAccessRights() {
            // Load from localStorage for now
            const stored = localStorage.getItem('accessRights');
            this.accessRights = stored ? JSON.parse(stored) : {};
        },

        saveAccessRights() {
            // Save to localStorage for now
            localStorage.setItem('accessRights', JSON.stringify(this.accessRights));
        },

        hasAccess(employeeId, sectionKey) {
            const key = `${employeeId}_${sectionKey}`;
            return this.accessRights[key] === true;
        },

        toggleAccess(employeeId, sectionKey, checked) {
            const key = `${employeeId}_${sectionKey}`;
            this.accessRights[key] = checked;
            this.saveAccessRights();

            // TODO: Send to backend API
            // await fetch(`/api/companies/${this.selectedCompanyId}/access`, {
            //     method: 'POST',
            //     body: JSON.stringify({
            //         employee_id: employeeId,
            //         section: sectionKey,
            //         granted: checked
            //     })
            // });
        },

        canManageAccess() {
            const selectedCompany = this.companies.find(c => c.id === this.selectedCompanyId);
            return selectedCompany && selectedCompany.pivot && selectedCompany.pivot.role === 'owner';
        }
    };
}
</script>
