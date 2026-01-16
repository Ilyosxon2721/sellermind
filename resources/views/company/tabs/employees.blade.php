<div x-data="employeesTab()">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Сотрудники</h2>
            <p class="text-sm text-gray-500 mt-1">Управление пользователями компании</p>
        </div>
        <button class="btn btn-primary text-sm" @click="openInviteModal()">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Добавить сотрудника
        </button>
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

    <!-- Employees List -->
    <div x-show="selectedCompanyId">
        <template x-if="loading">
            <div class="card">
                <div class="card-body text-center py-12">
                    <div class="spinner mx-auto"></div>
                    <p class="text-gray-500 mt-2">Загрузка...</p>
                </div>
            </div>
        </template>

        <template x-if="!loading && employees.length === 0">
            <div class="card">
                <div class="card-body text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <p class="text-gray-900 font-medium mb-1">Нет сотрудников</p>
                    <p class="text-gray-500 text-sm">Добавьте первого сотрудника в компанию</p>
                </div>
            </div>
        </template>

        <div x-show="!loading && employees.length > 0" class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th class="hidden sm:table-cell">Добавлен</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="employee in employees" :key="employee.id">
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-medium text-sm mr-3"
                                         x-text="employee.name.charAt(0).toUpperCase()">
                                    </div>
                                    <span class="font-medium" x-text="employee.name"></span>
                                </div>
                            </td>
                            <td class="text-gray-600" x-text="employee.email"></td>
                            <td>
                                <span class="badge"
                                      :class="{
                                          'badge-success': employee.pivot.role === 'owner',
                                          'badge-primary': employee.pivot.role === 'manager',
                                          'badge-gray': employee.pivot.role !== 'owner' && employee.pivot.role !== 'manager'
                                      }"
                                      x-text="getRoleLabel(employee.pivot.role)">
                                </span>
                            </td>
                            <td class="hidden sm:table-cell text-gray-500 text-sm" x-text="formatDate(employee.pivot.created_at)"></td>
                            <td>
                                <button
                                    class="btn btn-sm btn-ghost text-red-600"
                                    @click="removeEmployee(employee)"
                                    x-show="employee.pivot.role !== 'owner' && canManageEmployees()">
                                    Удалить
                                </button>
                                <span class="text-gray-400 text-sm" x-show="employee.pivot.role === 'owner'">Владелец</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- No Company Selected -->
    <div x-show="!selectedCompanyId && !loading" class="card">
        <div class="card-body text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500">Выберите компанию для просмотра сотрудников</p>
        </div>
    </div>

    <!-- Invite Employee Modal -->
    <div x-show="showInviteModal" class="modal-overlay" @click.self="closeInviteModal()">
        <div class="modal-content max-w-lg">
            <div class="modal-header">
                <h3 class="modal-title">Добавить сотрудника</h3>
                <button @click="closeInviteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="inviteEmployee()">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Имя сотрудника *</label>
                            <input type="text" class="form-input" x-model="inviteForm.name" required
                                   placeholder="Иван Иванов">
                        </div>
                        <div>
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-input" x-model="inviteForm.email" required
                                   placeholder="user@example.com">
                        </div>
                        <div>
                            <label class="form-label">Пароль *</label>
                            <input type="password" class="form-input" x-model="inviteForm.password" required
                                   placeholder="Минимум 6 символов" minlength="6">
                            <p class="text-xs text-gray-500 mt-1">Сообщите этот пароль сотруднику для входа в систему</p>
                        </div>
                        <div>
                            <label class="form-label">Роль *</label>
                            <select class="form-select" x-model="inviteForm.role" required>
                                <option value="manager">Менеджер</option>
                                <option value="owner">Владелец</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="btn btn-ghost" @click="closeInviteModal()">Отмена</button>
                        <button type="submit" class="btn btn-primary" :disabled="inviting">
                            <span x-show="!inviting">Добавить</span>
                            <span x-show="inviting">Добавление...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function employeesTab() {
    return {
        companies: [],
        selectedCompanyId: '',
        employees: [],
        loading: false,
        showInviteModal: false,
        inviting: false,
        inviteForm: {
            name: '',
            email: '',
            password: '',
            role: 'manager'
        },

        async init() {
            await this.loadCompanies();
        },

        async loadCompanies() {
            try {
                const response = await window.api.get('/companies');
                this.companies = response.data.companies || response.data.data || [];

                // Auto-select first company if available
                if (this.companies.length > 0) {
                    this.selectedCompanyId = this.companies[0].id;
                    await this.loadEmployees();
                }
            } catch (error) {
                console.error('Error loading companies:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить компании');
                }
            }
        },

        async loadEmployees() {
            if (!this.selectedCompanyId) {
                this.employees = [];
                return;
            }

            this.loading = true;
            try {
                // Fetch company members
                const response = await window.api.get(`/companies/${this.selectedCompanyId}/members`);
                this.employees = response.data.members || response.data.data || [];
            } catch (error) {
                console.error('Error loading employees:', error);
                this.employees = [];
                if (window.toast) {
                    window.toast.error('Не удалось загрузить сотрудников');
                }
            } finally {
                this.loading = false;
            }
        },

        openInviteModal() {
            if (!this.selectedCompanyId) {
                alert('Пожалуйста, выберите компанию');
                return;
            }
            this.inviteForm = { name: '', email: '', password: '', role: 'manager' };
            this.showInviteModal = true;
        },

        closeInviteModal() {
            this.showInviteModal = false;
        },

        async inviteEmployee() {
            if (!this.selectedCompanyId) {
                alert('Компания не выбрана');
                return;
            }

            this.inviting = true;
            try {
                await window.api.post(`/companies/${this.selectedCompanyId}/members`, this.inviteForm);
                this.closeInviteModal();
                await this.loadEmployees();
                if (window.toast) {
                    window.toast.success('Сотрудник добавлен');
                } else {
                    alert('Сотрудник добавлен');
                }
            } catch (error) {
                console.error('Error inviting employee:', error);
                const message = error.response?.data?.message || 'Не удалось добавить сотрудника';
                if (window.toast) {
                    window.toast.error('Ошибка: ' + message);
                } else {
                    alert('Ошибка: ' + message);
                }
            } finally {
                this.inviting = false;
            }
        },

        async removeEmployee(employee) {
            if (!confirm(`Удалить сотрудника ${employee.name} из компании?`)) {
                return;
            }

            try {
                await window.api.delete(`/companies/${this.selectedCompanyId}/members/${employee.id}`);
                await this.loadEmployees();
                if (window.toast) {
                    window.toast.success('Сотрудник удален');
                } else {
                    alert('Сотрудник удален');
                }
            } catch (error) {
                console.error('Error removing employee:', error);
                const message = error.response?.data?.message || 'Не удалось удалить сотрудника';
                if (window.toast) {
                    window.toast.error('Ошибка: ' + message);
                } else {
                    alert('Ошибка: ' + message);
                }
            }
        },

        canManageEmployees() {
            const selectedCompany = this.companies.find(c => c.id === this.selectedCompanyId);
            return selectedCompany && selectedCompany.pivot && selectedCompany.pivot.role === 'owner';
        },

        getRoleLabel(role) {
            const labels = {
                'owner': 'Владелец',
                'manager': 'Менеджер'
            };
            return labels[role] || role;
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        }
    };
}
</script>
