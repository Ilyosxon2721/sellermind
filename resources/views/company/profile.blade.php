@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50 browser-only" x-data="companyProfilePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Профиль компании</h1>
                    <p class="text-sm text-gray-500 mt-1">Управление компанией, сотрудниками и подпиской</p>
                </div>
            </div>
        </header>

        <!-- Tabs Navigation -->
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
            <nav class="flex space-x-6 overflow-x-auto">
                <button
                    @click="activeTab = 'companies'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'companies' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Компании
                </button>
                <button
                    @click="activeTab = 'employees'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'employees' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Сотрудники
                </button>
                <button
                    @click="activeTab = 'access'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'access' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Доступы
                </button>
                {{-- Биллинг - временно скрыт до завершения разработки
                <button
                    @click="activeTab = 'billing'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Биллинг
                </button>
                --}}
            </nav>
        </div>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Companies Tab -->
            <div x-show="activeTab === 'companies'">
                @include('company.tabs.companies')
            </div>

            <!-- Employees Tab -->
            <div x-show="activeTab === 'employees'">
                @include('company.tabs.employees')
            </div>

            <!-- Access Tab -->
            <div x-show="activeTab === 'access'">
                @include('company.tabs.access')
            </div>

            {{-- Billing Tab - временно скрыт до завершения разработки
            <div x-show="activeTab === 'billing'">
                @include('company.tabs.billing')
            </div>
            --}}
        </main>
    </div>
</div>

<script>
function companyProfilePage() {
    return {
        activeTab: 'companies',

        init() {
            // Get tab from URL hash if present
            const hash = window.location.hash.substring(1);
            if (['companies', 'employees', 'access'].includes(hash)) {
                this.activeTab = hash;
            }
        }
    };
}
</script>
{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    activeTab: 'companies',
    companies: [],
    employees: [],
    selectedCompanyId: '',
    loading: false,
    showCreateModal: false,
    showInviteModal: false,
    form: { name: '' },
    inviteForm: { email: '', role: 'manager' },
    tabs: [
        { key: 'companies', label: 'Компании', icon: 'building' },
        { key: 'employees', label: 'Сотрудники', icon: 'users' }
        // { key: 'billing', label: 'Биллинг', icon: 'card' } - временно скрыт до завершения разработки
    ],
    getToken() {
        const t = localStorage.getItem('_x_auth_token');
        if (t) try { return JSON.parse(t); } catch { return t; }
        return localStorage.getItem('auth_token');
    },
    async loadCompanies() {
        this.loading = true;
        try {
            const res = await fetch('/api/companies', { headers: { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                this.companies = data.companies || data.data || [];
                if (this.companies.length > 0 && !this.selectedCompanyId) {
                    this.selectedCompanyId = this.companies[0].id;
                    await this.loadEmployees();
                }
            }
        } catch (e) { console.error(e); }
        this.loading = false;
    },
    async loadEmployees() {
        if (!this.selectedCompanyId) return;
        try {
            const res = await fetch('/api/companies/' + this.selectedCompanyId + '/members', { headers: { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                this.employees = data.members || data.data || [];
            }
        } catch (e) { console.error(e); }
    },
    async saveCompany() {
        try {
            const res = await fetch('/api/companies', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form)
            });
            if (res.ok) {
                this.showCreateModal = false;
                this.form = { name: '' };
                await this.loadCompanies();
            }
        } catch (e) { console.error(e); }
    },
    async inviteEmployee() {
        if (!this.selectedCompanyId) return;
        try {
            const res = await fetch('/api/companies/' + this.selectedCompanyId + '/members', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.inviteForm)
            });
            if (res.ok) {
                this.showInviteModal = false;
                this.inviteForm = { email: '', role: 'manager' };
                await this.loadEmployees();
            }
        } catch (e) { console.error(e); }
    },
    getRoleLabel(role) {
        return { owner: 'Владелец', manager: 'Менеджер' }[role] || role;
    }
}" x-init="loadCompanies()" style="background: #f2f2f7;">
    <x-pwa-header title="Профиль компании" backUrl="/">
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        {{-- Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-3 hide-scrollbar">
            <template x-for="tab in tabs" :key="tab.key">
                <button @click="activeTab = tab.key" :class="activeTab === tab.key ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap flex-shrink-0" x-text="tab.label"></button>
            </template>
        </div>

        {{-- Companies Tab --}}
        <div x-show="activeTab === 'companies'" class="space-y-3">
            <div class="flex justify-between items-center">
                <p class="native-caption text-gray-500">Ваши компании</p>
                <button @click="showCreateModal = true" class="text-blue-500 font-medium text-sm">+ Добавить</button>
            </div>

            <template x-if="companies.length === 0 && !loading">
                <div class="native-card p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500">Нет компаний</p>
                </div>
            </template>

            <template x-for="company in companies" :key="company.id">
                <div class="native-card p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900" x-text="company.name"></p>
                            <p class="native-caption text-gray-500" x-text="(company.products_count || 0) + ' товаров'"></p>
                        </div>
                        <span x-show="company.pivot?.role === 'owner'" class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Владелец</span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Employees Tab --}}
        <div x-show="activeTab === 'employees'" class="space-y-3">
            <div class="native-card mb-3">
                <div class="p-3">
                    <select x-model="selectedCompanyId" @change="loadEmployees()" class="w-full px-3 py-2 rounded-lg bg-gray-50 border-0 text-base">
                        <option value="">Выберите компанию</option>
                        <template x-for="company in companies" :key="company.id">
                            <option :value="company.id" x-text="company.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div x-show="selectedCompanyId" class="flex justify-between items-center">
                <p class="native-caption text-gray-500">Сотрудники</p>
                <button @click="showInviteModal = true" class="text-blue-500 font-medium text-sm">+ Добавить</button>
            </div>

            <template x-if="selectedCompanyId && employees.length === 0">
                <div class="native-card p-6 text-center">
                    <p class="native-body text-gray-500">Нет сотрудников</p>
                </div>
            </template>

            <template x-for="employee in employees" :key="employee.id">
                <div class="native-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-medium" x-text="employee.name?.charAt(0).toUpperCase()"></div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900" x-text="employee.name"></p>
                            <p class="native-caption text-gray-500" x-text="employee.email"></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="employee.pivot?.role === 'owner' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'" x-text="getRoleLabel(employee.pivot?.role)"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Billing Tab - временно скрыт до завершения разработки --}}
    </main>

    {{-- Create Company Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50" @click.self="showCreateModal = false">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom, 20px);">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-lg">Новая компания</h3>
                <button @click="showCreateModal = false" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <div class="mb-4">
                    <label class="native-caption text-gray-500 mb-1 block">Название</label>
                    <input type="text" x-model="form.name" placeholder="Название компании" class="w-full px-4 py-3 rounded-xl bg-gray-50 border-0 text-base">
                </div>
                <button @click="saveCompany()" class="native-btn native-btn-primary w-full">Создать</button>
            </div>
        </div>
    </div>

    {{-- Invite Employee Modal --}}
    <div x-show="showInviteModal" x-cloak class="fixed inset-0 z-50" @click.self="showInviteModal = false">
        <div class="absolute inset-0 bg-black/50" @click="showInviteModal = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom, 20px);">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-lg">Добавить сотрудника</h3>
                <button @click="showInviteModal = false" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <div class="mb-4">
                    <label class="native-caption text-gray-500 mb-1 block">Email</label>
                    <input type="email" x-model="inviteForm.email" placeholder="user@example.com" class="w-full px-4 py-3 rounded-xl bg-gray-50 border-0 text-base">
                </div>
                <div class="mb-4">
                    <label class="native-caption text-gray-500 mb-1 block">Роль</label>
                    <select x-model="inviteForm.role" class="w-full px-4 py-3 rounded-xl bg-gray-50 border-0 text-base">
                        <option value="manager">Менеджер</option>
                        <option value="owner">Владелец</option>
                    </select>
                </div>
                <button @click="inviteEmployee()" class="native-btn native-btn-primary w-full">Добавить</button>
            </div>
        </div>
    </div>
</div>
@endsection
