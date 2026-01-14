@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="counterpartiesPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Контрагенты</h1>
                    <p class="text-sm text-gray-500 mt-1">Клиенты, поставщики и договоры</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadCounterparties()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <button class="btn btn-primary text-sm" @click="openCreateModal()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Добавить
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Поиск</label>
                            <input type="text" class="form-input" placeholder="Название, ИНН, телефон..." x-model="filters.search" @keydown.enter="loadCounterparties()">
                        </div>
                        <div>
                            <label class="form-label">Тип</label>
                            <select class="form-select" x-model="filters.type" @change="loadCounterparties()">
                                <option value="">Все</option>
                                <option value="individual">Физ. лица</option>
                                <option value="legal">Юр. лица</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button class="btn btn-primary flex-1 text-sm" @click="loadCounterparties()">Найти</button>
                            <button class="btn btn-ghost text-sm" @click="resetFilters()">Сброс</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Всего контрагентов</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="stats.total"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Юр. лица</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1" x-text="stats.legal"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Физ. лица</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" x-text="stats.individual"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">С договорами</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1" x-text="stats.withContracts"></p>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th class="hidden md:table-cell">Тип</th>
                            <th class="hidden lg:table-cell">ИНН</th>
                            <th class="hidden sm:table-cell">Телефон</th>
                            <th>Комиссия</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <div class="spinner mx-auto"></div>
                                    <p class="text-gray-500 mt-2">Загрузка...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && counterparties.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="empty-state-title">Контрагентов не найдено</p>
                                        <p class="empty-state-text">Добавьте первого контрагента</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in counterparties" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <div class="font-medium text-gray-900" x-text="item.short_name || item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.email"></div>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="badge" :class="item.type === 'legal' ? 'badge-primary' : 'badge-success'" x-text="item.type === 'legal' ? 'Юр. лицо' : 'Физ. лицо'"></span>
                                </td>
                                <td class="hidden lg:table-cell">
                                    <span class="text-sm text-gray-700" x-text="item.inn || '—'"></span>
                                </td>
                                <td class="hidden sm:table-cell">
                                    <span class="text-sm text-gray-700" x-text="item.phone || '—'"></span>
                                </td>
                                <td>
                                    <template x-if="item.contracts && item.contracts.length > 0">
                                        <span class="badge badge-warning" x-text="item.contracts[0].commission_percent + '%'"></span>
                                    </template>
                                    <template x-if="!item.contracts || item.contracts.length === 0">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button class="btn btn-ghost btn-sm" @click="viewCounterparty(item)" title="Просмотр">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button class="btn btn-ghost btn-sm" @click="openEditModal(item)" title="Редактировать">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button class="btn btn-ghost btn-sm text-red-600" @click="deleteCounterparty(item)" title="Удалить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between" x-show="totalPages > 1">
                    <div class="text-sm text-gray-500">
                        Страница <span x-text="currentPage"></span> из <span x-text="totalPages"></span>
                    </div>
                    <div class="flex gap-1">
                        <button class="btn btn-ghost btn-sm" @click="prevPage()" :disabled="currentPage <= 1">←</button>
                        <button class="btn btn-ghost btn-sm" @click="nextPage()" :disabled="currentPage >= totalPages">→</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showFormModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showFormModal = false"></div>
        <div class="modal max-w-2xl">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900" x-text="isEditing ? 'Редактировать контрагента' : 'Новый контрагент'"></h3>
            </div>
            <div class="modal-body">
                <div class="space-y-4">
                    <!-- Type -->
                    <div>
                        <label class="form-label">Тип контрагента</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="individual" x-model="form.type" class="form-radio">
                                <span>Физ. лицо</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" value="legal" x-model="form.type" class="form-radio">
                                <span>Юр. лицо</span>
                            </label>
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Название / ФИО *</label>
                            <input type="text" class="form-input" x-model="form.name" placeholder="ООО Рога и Копыта или Иванов И.И.">
                        </div>
                        <div>
                            <label class="form-label">Сокращённое название</label>
                            <input type="text" class="form-input" x-model="form.short_name" placeholder="РиК">
                        </div>
                        <div>
                            <label class="form-label">ИНН</label>
                            <input type="text" class="form-input" x-model="form.inn" placeholder="1234567890">
                        </div>
                    </div>

                    <!-- Legal entity fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-show="form.type === 'legal'">
                        <div>
                            <label class="form-label">КПП</label>
                            <input type="text" class="form-input" x-model="form.kpp">
                        </div>
                        <div>
                            <label class="form-label">ОГРН</label>
                            <input type="text" class="form-input" x-model="form.ogrn">
                        </div>
                        <div>
                            <label class="form-label">ОКПО</label>
                            <input type="text" class="form-input" x-model="form.okpo">
                        </div>
                    </div>

                    <!-- Contacts -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-input" x-model="form.phone" placeholder="+998 90 123-45-67">
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" x-model="form.email" placeholder="info@example.com">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="form-label">Адрес</label>
                        <textarea class="form-textarea" rows="2" x-model="form.actual_address" placeholder="Город, улица, дом..."></textarea>
                    </div>

                    <!-- Contact person -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Контактное лицо</label>
                            <input type="text" class="form-input" x-model="form.contact_person">
                        </div>
                        <div>
                            <label class="form-label">Должность</label>
                            <input type="text" class="form-input" x-model="form.contact_position">
                        </div>
                        <div>
                            <label class="form-label">Телефон контакта</label>
                            <input type="text" class="form-input" x-model="form.contact_phone">
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_customer" class="form-checkbox">
                            <span>Покупатель</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_supplier" class="form-checkbox">
                            <span>Поставщик</span>
                        </label>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="form-label">Примечания</label>
                        <textarea class="form-textarea" rows="2" x-model="form.notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showFormModal = false">Отмена</button>
                <button class="btn btn-primary" @click="saveCounterparty()" :disabled="saving">
                    <span x-show="saving">Сохранение...</span>
                    <span x-show="!saving" x-text="isEditing ? 'Сохранить' : 'Создать'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- View Modal with Contracts -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showViewModal = false"></div>
        <div class="modal max-w-3xl">
            <div class="modal-header flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" x-text="selectedCounterparty?.name"></h3>
                    <span class="badge mt-1" :class="selectedCounterparty?.type === 'legal' ? 'badge-primary' : 'badge-success'" x-text="selectedCounterparty?.type === 'legal' ? 'Юр. лицо' : 'Физ. лицо'"></span>
                </div>
                <button class="btn btn-ghost btn-sm" @click="showViewModal = false">✕</button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <div class="flex gap-2 mb-4 border-b">
                    <button class="px-4 py-2 text-sm font-medium" :class="viewTab === 'info' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'" @click="viewTab = 'info'">Информация</button>
                    <button class="px-4 py-2 text-sm font-medium" :class="viewTab === 'contracts' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'" @click="viewTab = 'contracts'">Договоры</button>
                </div>

                <!-- Info Tab -->
                <div x-show="viewTab === 'info'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">ИНН</label>
                            <p class="text-sm text-gray-900" x-text="selectedCounterparty?.inn || '—'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Телефон</label>
                            <p class="text-sm text-gray-900" x-text="selectedCounterparty?.phone || '—'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Email</label>
                            <p class="text-sm text-gray-900" x-text="selectedCounterparty?.email || '—'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Контактное лицо</label>
                            <p class="text-sm text-gray-900" x-text="selectedCounterparty?.contact_person || '—'"></p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Адрес</label>
                        <p class="text-sm text-gray-900" x-text="selectedCounterparty?.actual_address || '—'"></p>
                    </div>
                </div>

                <!-- Contracts Tab -->
                <div x-show="viewTab === 'contracts'" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-medium text-gray-900">Договоры</h4>
                        <button class="btn btn-primary btn-sm" @click="openContractModal()">+ Добавить договор</button>
                    </div>
                    
                    <template x-if="contracts.length === 0">
                        <p class="text-gray-500 text-center py-4">Договоров нет</p>
                    </template>
                    
                    <div class="space-y-2">
                        <template x-for="contract in contracts" :key="contract.id">
                            <div class="bg-gray-50 rounded-lg p-4 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900" x-text="'№' + contract.number"></span>
                                        <span class="badge" :class="contract.status === 'active' ? 'badge-success' : 'badge-gray'" x-text="getContractStatusLabel(contract.status)"></span>
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <span x-text="formatDate(contract.date)"></span>
                                        <template x-if="contract.valid_until">
                                            <span> — до <span x-text="formatDate(contract.valid_until)"></span></span>
                                        </template>
                                    </div>
                                    <div class="text-sm font-medium text-orange-600 mt-1">
                                        Комиссия: <span x-text="contract.commission_percent"></span>%
                                        <span class="text-gray-500" x-text="contract.commission_type === 'profit' ? '(от прибыли)' : '(от продаж)'"></span>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button class="btn btn-ghost btn-sm" @click="editContract(contract)">✎</button>
                                    <button class="btn btn-ghost btn-sm text-red-600" @click="deleteContract(contract)">✕</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showViewModal = false">Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Contract Modal -->
    <div x-show="showContractModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showContractModal = false"></div>
        <div class="modal max-w-lg">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900" x-text="contractForm.id ? 'Редактировать договор' : 'Новый договор'"></h3>
            </div>
            <div class="modal-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Номер договора *</label>
                        <input type="text" class="form-input" x-model="contractForm.number" placeholder="ДГ-001">
                    </div>
                    <div>
                        <label class="form-label">Дата *</label>
                        <input type="date" class="form-input" x-model="contractForm.date">
                    </div>
                </div>
                <div>
                    <label class="form-label">Название договора</label>
                    <input type="text" class="form-input" x-model="contractForm.name" placeholder="Договор поставки">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Действует с</label>
                        <input type="date" class="form-input" x-model="contractForm.valid_from">
                    </div>
                    <div>
                        <label class="form-label">Действует до</label>
                        <input type="date" class="form-input" x-model="contractForm.valid_until">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Комиссия (%)</label>
                        <input type="number" step="0.1" min="0" max="100" class="form-input" x-model="contractForm.commission_percent" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Тип комиссии</label>
                        <select class="form-select" x-model="contractForm.commission_type">
                            <option value="sales">От суммы продаж</option>
                            <option value="profit">От прибыли</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Статус</label>
                    <select class="form-select" x-model="contractForm.status">
                        <option value="draft">Черновик</option>
                        <option value="active">Действует</option>
                        <option value="suspended">Приостановлен</option>
                        <option value="terminated">Расторгнут</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Примечания</label>
                    <textarea class="form-textarea" rows="2" x-model="contractForm.notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showContractModal = false">Отмена</button>
                <button class="btn btn-primary" @click="saveContract()">
                    <span x-text="contractForm.id ? 'Сохранить' : 'Создать'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function counterpartiesPage() {
    return {
        loading: false,
        saving: false,
        counterparties: [],
        contracts: [],
        currentPage: 1,
        totalPages: 1,
        filters: { search: '', type: '' },
        stats: { total: 0, legal: 0, individual: 0, withContracts: 0 },
        
        showFormModal: false,
        showViewModal: false,
        showContractModal: false,
        isEditing: false,
        viewTab: 'info',
        selectedCounterparty: null,
        
        form: {
            id: null,
            type: 'individual',
            name: '',
            short_name: '',
            inn: '',
            kpp: '',
            ogrn: '',
            okpo: '',
            phone: '',
            email: '',
            actual_address: '',
            contact_person: '',
            contact_position: '',
            contact_phone: '',
            is_customer: true,
            is_supplier: false,
            notes: ''
        },
        contractForm: {
            id: null,
            number: '',
            name: '',
            date: new Date().toISOString().split('T')[0],
            valid_from: '',
            valid_until: '',
            commission_percent: 0,
            commission_type: 'sales',
            status: 'active',
            notes: ''
        },
        
        async init() {
            await this.loadCounterparties();
        },
        
        getEmptyForm() {
            return {
                id: null,
                type: 'individual',
                name: '',
                short_name: '',
                inn: '',
                kpp: '',
                ogrn: '',
                okpo: '',
                phone: '',
                email: '',
                actual_address: '',
                contact_person: '',
                contact_position: '',
                contact_phone: '',
                is_customer: true,
                is_supplier: false,
                notes: ''
            };
        },
        
        getEmptyContractForm() {
            return {
                id: null,
                number: '',
                name: '',
                date: new Date().toISOString().split('T')[0],
                valid_from: '',
                valid_until: '',
                commission_percent: 0,
                commission_type: 'sales',
                status: 'active',
                notes: ''
            };
        },
        
        async loadCounterparties() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 20
                });
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.type) params.append('type', this.filters.type);
                
                const resp = await fetch(`/api/counterparties?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.counterparties = data.data || [];
                    this.totalPages = data.meta?.last_page || 1;
                    this.calculateStats();
                }
            } catch (e) {
                console.error('Load error:', e);
            } finally {
                this.loading = false;
            }
        },
        
        calculateStats() {
            this.stats.total = this.counterparties.length;
            this.stats.legal = this.counterparties.filter(c => c.type === 'legal').length;
            this.stats.individual = this.counterparties.filter(c => c.type === 'individual').length;
            this.stats.withContracts = this.counterparties.filter(c => c.contracts?.length > 0).length;
        },
        
        resetFilters() {
            this.filters = { search: '', type: '' };
            this.loadCounterparties();
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadCounterparties();
            }
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadCounterparties();
            }
        },
        
        openCreateModal() {
            this.form = this.getEmptyForm();
            this.isEditing = false;
            this.showFormModal = true;
        },
        
        openEditModal(item) {
            this.form = { ...item };
            this.isEditing = true;
            this.showFormModal = true;
        },
        
        async saveCounterparty() {
            if (!this.form.name) {
                alert('Введите название');
                return;
            }
            
            this.saving = true;
            try {
                const url = this.isEditing ? `/api/counterparties/${this.form.id}` : '/api/counterparties';
                const method = this.isEditing ? 'PUT' : 'POST';
                
                const resp = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (resp.ok) {
                    this.showFormModal = false;
                    this.loadCounterparties();
                } else {
                    const err = await resp.json();
                    alert(err.message || 'Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            } finally {
                this.saving = false;
            }
        },
        
        async deleteCounterparty(item) {
            if (!confirm(`Удалить контрагента "${item.name}"?`)) return;
            
            try {
                const resp = await fetch(`/api/counterparties/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (resp.ok) {
                    this.loadCounterparties();
                }
            } catch (e) {
                alert('Ошибка удаления');
            }
        },
        
        async viewCounterparty(item) {
            this.selectedCounterparty = item;
            this.viewTab = 'info';
            this.showViewModal = true;
            await this.loadContracts(item.id);
        },
        
        async loadContracts(counterpartyId) {
            try {
                const resp = await fetch(`/api/counterparties/${counterpartyId}/contracts`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.contracts = data.data || [];
                }
            } catch (e) {
                this.contracts = [];
            }
        },
        
        openContractModal() {
            this.contractForm = this.getEmptyContractForm();
            this.showContractModal = true;
        },
        
        editContract(contract) {
            this.contractForm = { 
                ...contract,
                date: contract.date?.split('T')[0] || '',
                valid_from: contract.valid_from?.split('T')[0] || '',
                valid_until: contract.valid_until?.split('T')[0] || ''
            };
            this.showContractModal = true;
        },
        
        async saveContract() {
            if (!this.contractForm.number || !this.contractForm.date) {
                alert('Заполните номер и дату договора');
                return;
            }
            
            try {
                const url = this.contractForm.id 
                    ? `/api/counterparties/${this.selectedCounterparty.id}/contracts/${this.contractForm.id}`
                    : `/api/counterparties/${this.selectedCounterparty.id}/contracts`;
                const method = this.contractForm.id ? 'PUT' : 'POST';
                
                const resp = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.contractForm)
                });
                
                if (resp.ok) {
                    this.showContractModal = false;
                    await this.loadContracts(this.selectedCounterparty.id);
                    this.loadCounterparties();
                } else {
                    alert('Ошибка сохранения договора');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },
        
        async deleteContract(contract) {
            if (!confirm('Удалить договор?')) return;
            
            try {
                await fetch(`/api/counterparties/${this.selectedCounterparty.id}/contracts/${contract.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                await this.loadContracts(this.selectedCounterparty.id);
                this.loadCounterparties();
            } catch (e) {
                alert('Ошибка удаления');
            }
        },
        
        getContractStatusLabel(status) {
            const labels = {
                draft: 'Черновик',
                active: 'Действует',
                suspended: 'Приостановлен',
                terminated: 'Расторгнут',
                expired: 'Истёк'
            };
            return labels[status] || status;
        },
        
        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        }
    }
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="counterpartiesPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Контрагенты" backUrl="/">
        <button @click="openCreateModal()" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadCounterparties">

        {{-- Search --}}
        <div class="px-4 py-4">
            <div class="native-card">
                <input type="text" class="native-input w-full" placeholder="Поиск..." x-model="filters.search" @keydown.enter="loadCounterparties()">
                <div class="flex gap-2 mt-3">
                    <select class="native-input flex-1" x-model="filters.type" @change="loadCounterparties()">
                        <option value="">Все типы</option>
                        <option value="individual">Физ. лица</option>
                        <option value="legal">Юр. лица</option>
                    </select>
                    <button class="native-btn" @click="loadCounterparties()">Найти</button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="px-4 grid grid-cols-2 gap-3 mb-4">
            <div class="native-card text-center py-3">
                <p class="text-xl font-bold text-gray-900" x-text="stats.total">0</p>
                <p class="native-caption">Всего</p>
            </div>
            <div class="native-card text-center py-3">
                <p class="text-xl font-bold text-purple-600" x-text="stats.withContracts">0</p>
                <p class="native-caption">С договорами</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="4" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && counterparties.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="native-body font-semibold mb-2">Нет контрагентов</p>
                <button @click="openCreateModal()" class="text-blue-600 font-medium">Добавить →</button>
            </div>
        </div>

        {{-- List --}}
        <div x-show="!loading && counterparties.length > 0" class="px-4 space-y-3 pb-4">
            <template x-for="item in counterparties" :key="item.id">
                <div class="native-card" @click="viewCounterparty(item)">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <p class="native-body font-semibold" x-text="item.short_name || item.name"></p>
                            <p class="native-caption" x-text="item.email || item.phone || '—'"></p>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="item.type === 'legal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" x-text="item.type === 'legal' ? 'Юр.' : 'Физ.'"></span>
                    </div>
                    <div class="flex items-center justify-between native-caption">
                        <span x-text="item.inn ? 'ИНН: ' + item.inn : '—'"></span>
                        <template x-if="item.contracts && item.contracts.length > 0">
                            <span class="text-orange-600 font-medium" x-text="item.contracts[0].commission_percent + '%'"></span>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="totalPages > 1" class="flex items-center justify-between py-3">
                <span class="native-caption" x-text="'Стр. ' + currentPage + ' из ' + totalPages"></span>
                <div class="flex gap-2">
                    <button class="native-btn" @click="prevPage()" :disabled="currentPage <= 1">←</button>
                    <button class="native-btn" @click="nextPage()" :disabled="currentPage >= totalPages">→</button>
                </div>
            </div>
        </div>
    </main>

    {{-- Create/Edit Modal --}}
    <div x-show="showFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl w-full max-h-[90vh] overflow-hidden" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="p-5 border-b border-gray-100">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="text-lg font-bold" x-text="isEditing ? 'Редактировать' : 'Новый контрагент'"></h3>
            </div>
            <div class="p-5 overflow-y-auto max-h-[60vh] space-y-3">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2">
                        <input type="radio" value="individual" x-model="form.type"> Физ. лицо
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" value="legal" x-model="form.type"> Юр. лицо
                    </label>
                </div>
                <input type="text" class="native-input w-full" x-model="form.name" placeholder="Название / ФИО *">
                <input type="text" class="native-input w-full" x-model="form.short_name" placeholder="Сокращённое название">
                <input type="text" class="native-input w-full" x-model="form.inn" placeholder="ИНН">
                <input type="text" class="native-input w-full" x-model="form.phone" placeholder="Телефон">
                <input type="email" class="native-input w-full" x-model="form.email" placeholder="Email">
                <textarea class="native-input w-full" rows="2" x-model="form.actual_address" placeholder="Адрес"></textarea>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.is_customer"> Покупатель
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.is_supplier"> Поставщик
                    </label>
                </div>
            </div>
            <div class="p-5 border-t border-gray-100 flex gap-2">
                <button @click="saveCounterparty()" class="native-btn native-btn-primary flex-1" :disabled="saving">
                    <span x-show="!saving" x-text="isEditing ? 'Сохранить' : 'Создать'"></span>
                    <span x-show="saving">...</span>
                </button>
                <button @click="showFormModal = false" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>

    {{-- View Modal --}}
    <div x-show="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl w-full max-h-[85vh] overflow-hidden" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="p-5 border-b border-gray-100">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold" x-text="selectedCounterparty?.name"></h3>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full mt-1 inline-block" :class="selectedCounterparty?.type === 'legal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" x-text="selectedCounterparty?.type === 'legal' ? 'Юр. лицо' : 'Физ. лицо'"></span>
                    </div>
                    <button @click="openEditModal(selectedCounterparty)" class="text-blue-600 font-medium text-sm">Ред.</button>
                </div>
            </div>
            <div class="flex border-b border-gray-100">
                <button class="flex-1 py-3 text-sm font-medium" :class="viewTab === 'info' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'" @click="viewTab = 'info'">Инфо</button>
                <button class="flex-1 py-3 text-sm font-medium" :class="viewTab === 'contracts' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'" @click="viewTab = 'contracts'">Договоры</button>
            </div>
            <div class="p-5 overflow-y-auto max-h-[50vh]">
                {{-- Info --}}
                <div x-show="viewTab === 'info'" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="native-caption">ИНН</p><p class="native-body" x-text="selectedCounterparty?.inn || '—'"></p></div>
                        <div><p class="native-caption">Телефон</p><p class="native-body" x-text="selectedCounterparty?.phone || '—'"></p></div>
                        <div><p class="native-caption">Email</p><p class="native-body" x-text="selectedCounterparty?.email || '—'"></p></div>
                        <div><p class="native-caption">Контакт</p><p class="native-body" x-text="selectedCounterparty?.contact_person || '—'"></p></div>
                    </div>
                    <div><p class="native-caption">Адрес</p><p class="native-body" x-text="selectedCounterparty?.actual_address || '—'"></p></div>
                </div>
                {{-- Contracts --}}
                <div x-show="viewTab === 'contracts'" class="space-y-3">
                    <button @click="openContractModal()" class="native-btn native-btn-primary w-full">+ Добавить договор</button>
                    <template x-for="contract in contracts" :key="contract.id">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium" x-text="'№' + contract.number"></span>
                                <span class="px-2 py-0.5 text-xs rounded-full" :class="contract.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" x-text="getContractStatusLabel(contract.status)"></span>
                            </div>
                            <p class="native-caption mt-1" x-text="formatDate(contract.date)"></p>
                            <p class="text-sm font-medium text-orange-600 mt-1">Комиссия: <span x-text="contract.commission_percent"></span>%</p>
                        </div>
                    </template>
                    <p x-show="contracts.length === 0" class="text-center py-4 native-caption">Нет договоров</p>
                </div>
            </div>
            <div class="p-5 border-t border-gray-100">
                <button @click="showViewModal = false" class="native-btn w-full">Закрыть</button>
            </div>
        </div>
    </div>

    {{-- Contract Modal --}}
    <div x-show="showContractModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl p-5 w-full max-w-md" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-bold mb-4" x-text="contractForm.id ? 'Редактировать договор' : 'Новый договор'"></h3>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" class="native-input" x-model="contractForm.number" placeholder="Номер *">
                    <input type="date" class="native-input" x-model="contractForm.date">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.1" class="native-input" x-model="contractForm.commission_percent" placeholder="Комиссия %">
                    <select class="native-input" x-model="contractForm.status">
                        <option value="active">Действует</option>
                        <option value="draft">Черновик</option>
                        <option value="suspended">Приостановлен</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button @click="saveContract()" class="native-btn native-btn-primary flex-1">Сохранить</button>
                <button @click="showContractModal = false" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>
</div>
@endsection
