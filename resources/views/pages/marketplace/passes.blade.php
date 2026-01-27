@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div x-data="passesManager({{ $accountId }})" x-init="init()" class="browser-only container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Пропуски на склады WB</h1>
            <p class="text-sm text-gray-600 mt-1">Управление пропусками для доступа на склады Wildberries</p>
        </div>
        <button @click="showCreateModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать пропуск
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Всего пропусков</div>
            <div class="text-2xl font-bold text-gray-800" x-text="passes.length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Активные</div>
            <div class="text-2xl font-bold text-green-600" x-text="activePassesCount"></div>
        </div>
        <div class="bg-orange-50 rounded-lg border border-orange-200 p-4" x-show="expiringPassesCount > 0">
            <div class="text-sm text-orange-700">⚠️ Истекают скоро</div>
            <div class="text-2xl font-bold text-orange-600" x-text="expiringPassesCount"></div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Passes List -->
    <div x-show="!loading" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ФИО</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Авто</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Склад</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Период</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="pass in passes" :key="pass.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900" x-text="`${pass.lastName} ${pass.firstName}`"></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div x-show="pass.carModel" x-text="`${pass.carModel} (${pass.carNumber})`"></div>
                            <div x-show="!pass.carModel" class="text-gray-400">-</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="pass.officeId"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span x-text="formatDate(pass.dateFrom)"></span> - <span x-text="formatDate(pass.dateTo)"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-green-100 text-green-800': isPassActive(pass),
                                'bg-orange-100 text-orange-800': isPassExpiringSoon(pass),
                                'bg-gray-100 text-gray-800': isPassExpired(pass)
                            }" class="px-2 py-1 text-xs font-semibold rounded-full" x-text="getPassStatus(pass)"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <button @click="deletePass(pass.id)"
                                    :disabled="deletingPassId === pass.id"
                                    class="text-red-600 hover:text-red-800 disabled:opacity-50 flex items-center">
                                <svg x-show="deletingPassId === pass.id" class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="deletingPassId === pass.id ? 'Удаление...' : 'Удалить'"></span>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Empty State -->
        <div x-show="passes.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Нет пропусков</h3>
            <p class="mt-1 text-sm text-gray-500">Создайте пропуск для доступа на склад</p>
        </div>
    </div>

    <!-- Create Pass Modal -->
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div @click.away="showCreateModal = false" class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">Создать пропуск</h3>

            <div class="space-y-3">
                <input type="text" x-model="newPass.firstName" placeholder="Имя" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <input type="text" x-model="newPass.lastName" placeholder="Фамилия" class="w-full border border-gray-300 rounded-lg px-4 py-2">

                <select x-model="newPass.officeId" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Выберите склад</option>
                    <template x-for="office in offices" :key="office.id">
                        <option :value="office.id" x-text="office.name"></option>
                    </template>
                </select>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" x-model="newPass.dateFrom" class="border border-gray-300 rounded-lg px-4 py-2">
                    <input type="date" x-model="newPass.dateTo" class="border border-gray-300 rounded-lg px-4 py-2">
                </div>

                <details class="border rounded p-2">
                    <summary class="cursor-pointer text-sm font-medium">Данные автомобиля (опционально)</summary>
                    <div class="mt-2 space-y-2">
                        <input type="text" x-model="newPass.carModel" placeholder="Модель авто" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <input type="text" x-model="newPass.carNumber" placeholder="Номер авто" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </details>
            </div>

            <div class="flex gap-2 mt-4">
                <button @click="createPass()"
                        :disabled="creating"
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg disabled:opacity-50 flex items-center justify-center">
                    <svg x-show="creating" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="creating ? 'Создание...' : 'Создать'"></span>
                </button>
                <button @click="showCreateModal = false" :disabled="creating" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg disabled:opacity-50">Отмена</button>
            </div>
        </div>
    </div>
</div>

<script>
function passesManager(accountId) {
    return {
        accountId: accountId,
        passes: [],
        offices: [],
        loading: true,
        creating: false,
        deletingPassId: null,
        showCreateModal: false,
        newPass: {
            firstName: '',
            lastName: '',
            officeId: '',
            dateFrom: '',
            dateTo: '',
            carModel: '',
            carNumber: ''
        },

        get activePassesCount() {
            return this.passes.filter(p => this.isPassActive(p)).length;
        },

        get expiringPassesCount() {
            return this.passes.filter(p => this.isPassExpiringSoon(p)).length;
        },

        async init() {
            await this.loadOffices();
            await this.loadPasses();
        },

        getAuthHeaders() {
            const token = this.$store.auth.token || localStorage.getItem('auth_token');
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        },

        async loadOffices() {
            try {
                const response = await fetch(`/api/marketplace/wb/accounts/${this.accountId}/passes/offices`, {
                    headers: this.getAuthHeaders()
                });
                const data = await response.json();
                this.offices = data.offices || [];
            } catch (error) {
                console.error('Failed to load offices:', error);
            }
        },

        async loadPasses() {
            this.loading = true;
            try {
                const response = await fetch(`/api/marketplace/wb/accounts/${this.accountId}/passes`, {
                    headers: this.getAuthHeaders()
                });
                const data = await response.json();
                this.passes = data.passes || [];
            } catch (error) {
                console.error('Failed to load passes:', error);
            } finally {
                this.loading = false;
            }
        },

        async createPass() {
            if (!this.newPass.firstName || !this.newPass.lastName || !this.newPass.officeId || !this.newPass.dateFrom || !this.newPass.dateTo) {
                alert('Заполните все обязательные поля');
                return;
            }

            this.creating = true;
            try {
                const response = await fetch(`/api/marketplace/wb/accounts/${this.accountId}/passes`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.newPass)
                });

                if (response.ok) {
                    this.showCreateModal = false;
                    this.newPass = { firstName: '', lastName: '', officeId: '', dateFrom: '', dateTo: '', carModel: '', carNumber: '' };
                    await this.loadPasses();
                    alert('Пропуск успешно создан');
                } else {
                    alert('Ошибка создания пропуска');
                }
            } catch (error) {
                console.error('Failed to create pass:', error);
                alert('Ошибка создания пропуска');
            } finally {
                this.creating = false;
            }
        },

        async deletePass(passId) {
            if (!confirm('Удалить пропуск?')) return;

            this.deletingPassId = passId;
            try {
                const response = await fetch(`/api/marketplace/wb/accounts/${this.accountId}/passes/${passId}`, {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                });

                if (response.ok) {
                    await this.loadPasses();
                    alert('Пропуск удалён');
                } else {
                    alert('Ошибка удаления пропуска');
                }
            } catch (error) {
                console.error('Failed to delete pass:', error);
                alert('Ошибка удаления пропуска');
            } finally {
                this.deletingPassId = null;
            }
        },

        isPassActive(pass) {
            const today = new Date().setHours(0, 0, 0, 0);
            const dateTo = new Date(pass.dateTo).setHours(0, 0, 0, 0);
            return dateTo >= today;
        },

        isPassExpired(pass) {
            const today = new Date().setHours(0, 0, 0, 0);
            const dateTo = new Date(pass.dateTo).setHours(0, 0, 0, 0);
            return dateTo < today;
        },

        isPassExpiringSoon(pass) {
            const today = new Date().setHours(0, 0, 0, 0);
            const dateTo = new Date(pass.dateTo).setHours(0, 0, 0, 0);
            const weekFromNow = new Date(today + 7 * 24 * 60 * 60 * 1000);
            return dateTo >= today && dateTo <= weekFromNow;
        },

        getPassStatus(pass) {
            if (this.isPassExpired(pass)) return 'Истёк';
            if (this.isPassExpiringSoon(pass)) return 'Истекает скоро';
            return 'Активен';
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('ru-RU');
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="passesManager({{ $accountId }})" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Пропуски WB" :backUrl="'/marketplace/wb/' . $accountId">
        <button @click="showCreateModal = true" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            + Создать
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadPasses">

        {{-- Stats --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-gray-800" x-text="passes.length">0</p>
                <p class="native-caption">Всего</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-green-600" x-text="activePassesCount">0</p>
                <p class="native-caption">Активных</p>
            </div>
        </div>

        {{-- Expiring Warning --}}
        <div x-show="expiringPassesCount > 0" class="px-4 mb-4">
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-3">
                <p class="text-sm text-orange-700">⚠️ Истекают скоро: <span class="font-bold" x-text="expiringPassesCount"></span></p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="4" />
        </div>

        {{-- Passes List --}}
        <div x-show="!loading" class="px-4 space-y-3">
            <template x-for="pass in passes" :key="pass.id">
                <div class="native-card">
                    <div class="flex items-center justify-between mb-2">
                        <p class="native-body font-semibold" x-text="`${pass.lastName} ${pass.firstName}`"></p>
                        <span :class="{
                            'bg-green-100 text-green-800': isPassActive(pass) && !isPassExpiringSoon(pass),
                            'bg-orange-100 text-orange-800': isPassExpiringSoon(pass),
                            'bg-gray-100 text-gray-800': isPassExpired(pass)
                        }" class="px-2 py-0.5 text-xs font-medium rounded-full" x-text="getPassStatus(pass)"></span>
                    </div>
                    <div class="native-caption space-y-1">
                        <p x-show="pass.carModel"><span class="text-gray-400">Авто:</span> <span x-text="`${pass.carModel} (${pass.carNumber})`"></span></p>
                        <p><span class="text-gray-400">Склад:</span> <span x-text="pass.officeId"></span></p>
                        <p><span class="text-gray-400">Период:</span> <span x-text="formatDate(pass.dateFrom)"></span> - <span x-text="formatDate(pass.dateTo)"></span></p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <button @click="deletePass(pass.id)"
                                :disabled="deletingPassId === pass.id"
                                class="text-sm text-red-600 disabled:opacity-50 flex items-center">
                            <svg x-show="deletingPassId === pass.id" class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="deletingPassId === pass.id ? 'Удаление...' : 'Удалить'"></span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="passes.length === 0 && !loading" class="text-center py-12 native-caption">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                <p>Нет пропусков</p>
                <button @click="showCreateModal = true" class="native-btn native-btn-primary mt-4">
                    Создать пропуск
                </button>
            </div>
        </div>
    </main>

    {{-- Create Modal (same for both modes) --}}
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div @click.away="showCreateModal = false" class="bg-white rounded-t-2xl p-5 w-full max-w-md" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-bold mb-4">Создать пропуск</h3>

            <div class="space-y-3">
                <input type="text" x-model="newPass.firstName" placeholder="Имя" class="native-input w-full">
                <input type="text" x-model="newPass.lastName" placeholder="Фамилия" class="native-input w-full">

                <select x-model="newPass.officeId" class="native-input w-full">
                    <option value="">Выберите склад</option>
                    <template x-for="office in offices" :key="office.id">
                        <option :value="office.id" x-text="office.name"></option>
                    </template>
                </select>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" x-model="newPass.dateFrom" class="native-input">
                    <input type="date" x-model="newPass.dateTo" class="native-input">
                </div>

                <details class="border border-gray-200 rounded-xl p-3">
                    <summary class="cursor-pointer text-sm font-medium">Данные автомобиля</summary>
                    <div class="mt-3 space-y-2">
                        <input type="text" x-model="newPass.carModel" placeholder="Модель авто" class="native-input w-full">
                        <input type="text" x-model="newPass.carNumber" placeholder="Номер авто" class="native-input w-full">
                    </div>
                </details>
            </div>

            <div class="flex gap-2 mt-4">
                <button @click="createPass()"
                        :disabled="creating"
                        class="native-btn native-btn-primary flex-1 disabled:opacity-50 flex items-center justify-center">
                    <svg x-show="creating" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="creating ? 'Создание...' : 'Создать'"></span>
                </button>
                <button @click="showCreateModal = false" :disabled="creating" class="native-btn flex-1 disabled:opacity-50">Отмена</button>
            </div>
        </div>
    </div>
</div>
@endsection
