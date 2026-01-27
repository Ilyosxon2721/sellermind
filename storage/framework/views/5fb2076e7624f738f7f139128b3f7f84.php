<div x-data="companiesTab()">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Список компаний</h2>
        <button class="btn btn-primary text-sm" @click="openCreateModal()">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить компанию
        </button>
    </div>

    <!-- Companies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-if="loading">
            <div class="col-span-full text-center py-12">
                <div class="spinner mx-auto"></div>
                <p class="text-gray-500 mt-2">Загрузка...</p>
            </div>
        </template>

        <template x-if="!loading && companies.length === 0">
            <div class="col-span-full card">
                <div class="card-body text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2-2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-gray-900 font-medium mb-1">Нет компаний</p>
                    <p class="text-gray-500 text-sm">Создайте вашу первую компанию</p>
                </div>
            </div>
        </template>

        <template x-for="company in companies" :key="company.id">
            <div class="card hover:shadow-md transition">
                <div class="card-body">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 mb-1" x-text="company.name"></h3>
                            <p class="text-xs text-gray-500" x-text="company.slug"></p>
                        </div>
                        <span class="badge badge-success text-xs" x-show="company.pivot && company.pivot.role === 'owner'">Владелец</span>
                        <span class="badge badge-gray text-xs" x-show="company.pivot && company.pivot.role !== 'owner'" x-text="company.pivot ? company.pivot.role : ''"></span>
                    </div>

                    <div class="text-sm text-gray-600 space-y-1 mb-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span x-text="`${company.products_count || 0} товаров`"></span>
                        </div>
                        <div class="flex items-center" x-show="company.created_at">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="`Создана: ${formatDate(company.created_at)}`"></span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-ghost flex-1" @click="viewCompany(company)">
                            Подробнее
                        </button>
                        <button
                            class="btn btn-sm btn-ghost"
                            @click="openEditModal(company)"
                            x-show="company.pivot && company.pivot.role === 'owner'">
                            Изменить
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal" class="modal-overlay" @click.self="closeModal()">
        <div class="modal-content max-w-2xl">
            <div class="modal-header">
                <h3 class="modal-title" x-text="editingCompany ? 'Редактировать компанию' : 'Новая компания'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="saveCompany()">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Название компании *</label>
                            <input type="text" class="form-input" x-model="form.name" required>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="btn btn-ghost" @click="closeModal()">Отмена</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="!saving">Сохранить</span>
                            <span x-show="saving">Сохранение...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Company Modal -->
    <div x-show="showViewModal" class="modal-overlay" @click.self="showViewModal = false">
        <div class="modal-content max-w-3xl">
            <div class="modal-header">
                <h3 class="modal-title">Информация о компании</h3>
                <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" x-show="viewingCompany">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Название</label>
                        <p class="text-gray-900" x-text="viewingCompany?.name"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Идентификатор</label>
                        <p class="text-gray-900 font-mono text-sm" x-text="viewingCompany?.slug"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Товаров</label>
                        <p class="text-gray-900" x-text="`${viewingCompany?.products_count || 0} шт.`"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Дата создания</label>
                        <p class="text-gray-900" x-text="formatDate(viewingCompany?.created_at)"></p>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button class="btn btn-ghost" @click="showViewModal = false">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function companiesTab() {
    return {
        companies: [],
        loading: false,
        showModal: false,
        showViewModal: false,
        editingCompany: null,
        viewingCompany: null,
        saving: false,
        form: {
            name: ''
        },

        async init() {
            await this.loadCompanies();
        },

        async loadCompanies() {
            this.loading = true;
            try {
                const response = await window.api.get('/companies');
                this.companies = response.data.companies || response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить компании');
                }
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.editingCompany = null;
            this.form = { name: '' };
            this.showModal = true;
        },

        openEditModal(company) {
            this.editingCompany = company;
            this.form = { name: company.name || '' };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingCompany = null;
        },

        viewCompany(company) {
            this.viewingCompany = company;
            this.showViewModal = true;
        },

        async saveCompany() {
            this.saving = true;
            try {
                let response;
                if (this.editingCompany) {
                    // Update existing company
                    response = await window.api.put(`/companies/${this.editingCompany.id}`, this.form);
                } else {
                    // Create new company
                    response = await window.api.post('/companies', this.form);
                }

                this.closeModal();
                await this.loadCompanies();

                // Reload auth store companies to sync with rest of app
                if (window.Alpine && window.Alpine.store('auth')) {
                    await window.Alpine.store('auth').loadCompanies();
                }

                if (window.toast) {
                    window.toast.success(this.editingCompany ? 'Компания обновлена' : 'Компания создана');
                } else {
                    alert(this.editingCompany ? 'Компания обновлена' : 'Компания создана');
                }
            } catch (error) {
                console.error('Error saving company:', error);
                const message = error.response?.data?.message || 'Не удалось сохранить';
                if (window.toast) {
                    window.toast.error('Ошибка: ' + message);
                } else {
                    alert('Ошибка: ' + message);
                }
            } finally {
                this.saving = false;
            }
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    };
}
</script>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\company\tabs\companies.blade.php ENDPATH**/ ?>