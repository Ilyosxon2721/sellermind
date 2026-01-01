@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50" x-data="companiesPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Компании</h1>
                    <p class="text-sm text-gray-500 mt-1">Управление компаниями и организациями</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadCompanies()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <button class="btn btn-primary text-sm" @click="openCreateModal()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Добавить компанию
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Search -->
            <div class="card">
                <div class="card-body">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="form-label">Поиск</label>
                            <input type="text" class="form-input" placeholder="Название, ИНН..." x-model="search" @keydown.enter="loadCompanies()">
                        </div>
                        <div class="flex items-end gap-2">
                            <button class="btn btn-primary text-sm" @click="loadCompanies()">Найти</button>
                            <button class="btn btn-ghost text-sm" @click="search = ''; loadCompanies()">Сброс</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th class="hidden md:table-cell">ИНН</th>
                            <th class="hidden lg:table-cell">Адрес</th>
                            <th class="hidden sm:table-cell">Телефон</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="5" class="text-center py-12">
                                    <div class="spinner mx-auto"></div>
                                    <p class="text-gray-500 mt-2">Загрузка...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && companies.length === 0">
                            <tr>
                                <td colspan="5" class="text-center py-12 text-gray-500">
                                    Компании не найдены
                                </td>
                            </tr>
                        </template>
                        <template x-for="company in companies" :key="company.id">
                            <tr>
                                <td x-text="company.name"></td>
                                <td class="hidden md:table-cell" x-text="company.inn || '-'"></td>
                                <td class="hidden lg:table-cell" x-text="company.address || '-'"></td>
                                <td class="hidden sm:table-cell" x-text="company.phone || '-'"></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-sm btn-ghost" @click="openEditModal(company)">
                                            Изменить
                                        </button>
                                        <button class="btn btn-sm btn-ghost text-red-600" @click="deleteCompany(company)">
                                            Удалить
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </main>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="form-label">Название компании *</label>
                            <input type="text" class="form-input" x-model="form.name" required>
                        </div>
                        <div>
                            <label class="form-label">ИНН</label>
                            <input type="text" class="form-input" x-model="form.inn">
                        </div>
                        <div>
                            <label class="form-label">КПП</label>
                            <input type="text" class="form-input" x-model="form.kpp">
                        </div>
                        <div>
                            <label class="form-label">ОГРН</label>
                            <input type="text" class="form-input" x-model="form.ogrn">
                        </div>
                        <div>
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-input" x-model="form.phone">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" x-model="form.email">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Адрес</label>
                            <textarea class="form-input" rows="2" x-model="form.address"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="btn btn-ghost" @click="closeModal()">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function companiesPage() {
    return {
        companies: [],
        loading: false,
        showModal: false,
        editingCompany: null,
        search: '',
        form: {
            name: '',
            inn: '',
            kpp: '',
            ogrn: '',
            phone: '',
            email: '',
            address: ''
        },

        async init() {
            await this.loadCompanies();
        },

        async loadCompanies() {
            this.loading = true;
            try {
                let url = '/api/companies';
                if (this.search) {
                    url += '?search=' + encodeURIComponent(this.search);
                }

                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.companies = data.data || data || [];
                } else {
                    console.error('Failed to load companies:', response.status);
                }
            } catch (error) {
                console.error('Error loading companies:', error);
                alert('Ошибка при загрузке компаний');
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.editingCompany = null;
            this.form = {
                name: '',
                inn: '',
                kpp: '',
                ogrn: '',
                phone: '',
                email: '',
                address: ''
            };
            this.showModal = true;
        },

        openEditModal(company) {
            this.editingCompany = company;
            this.form = {
                name: company.name || '',
                inn: company.inn || '',
                kpp: company.kpp || '',
                ogrn: company.ogrn || '',
                phone: company.phone || '',
                email: company.email || '',
                address: company.address || ''
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingCompany = null;
        },

        async saveCompany() {
            try {
                const url = this.editingCompany
                    ? `/api/companies/${this.editingCompany.id}`
                    : '/api/companies';

                const method = this.editingCompany ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    this.closeModal();
                    await this.loadCompanies();
                    alert(this.editingCompany ? 'Компания обновлена' : 'Компания создана');
                } else {
                    const error = await response.json();
                    alert('Ошибка: ' + (error.message || 'Не удалось сохранить'));
                }
            } catch (error) {
                console.error('Error saving company:', error);
                alert('Ошибка при сохранении компании');
            }
        },

        async deleteCompany(company) {
            if (!confirm(`Удалить компанию "${company.name}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/companies/${company.id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.loadCompanies();
                    alert('Компания удалена');
                } else {
                    alert('Ошибка при удалении компании');
                }
            } catch (error) {
                console.error('Error deleting company:', error);
                alert('Ошибка при удалении компании');
            }
        }
    };
}
</script>
@endsection
