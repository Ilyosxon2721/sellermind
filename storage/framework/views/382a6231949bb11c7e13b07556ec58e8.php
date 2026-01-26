<?php $__env->startSection('content'); ?>

<div class="browser-only flex h-screen bg-gray-50" x-data="companiesPage()">
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
                const params = this.search ? { search: this.search } : {};
                const response = await window.api.companies.list(params);
                this.companies = response.data || response || [];
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


<div class="pwa-only min-h-screen" x-data="companiesPage()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Компании','backUrl' => '/']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Компании','backUrl' => '/']); ?>
        <button @click="openCreateModal()" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadCompanies">

        
        <div class="px-4 py-4">
            <div class="native-card flex gap-2">
                <input type="text" class="native-input flex-1" placeholder="Поиск..." x-model="search" @keydown.enter="loadCompanies()">
                <button class="native-btn" @click="loadCompanies()">Найти</button>
            </div>
        </div>

        
        <div x-show="loading" class="px-4">
            <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 4]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 4]); ?>
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

        
        <div x-show="!loading && companies.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="native-body font-semibold mb-2">Нет компаний</p>
                <button @click="openCreateModal()" class="text-blue-600 font-medium">Добавить →</button>
            </div>
        </div>

        
        <div x-show="!loading && companies.length > 0" class="px-4 space-y-3 pb-4">
            <template x-for="company in companies" :key="company.id">
                <div class="native-card" @click="openEditModal(company)">
                    <p class="native-body font-semibold" x-text="company.name"></p>
                    <div class="grid grid-cols-2 gap-2 mt-2 native-caption">
                        <div><span class="text-gray-400">ИНН:</span> <span x-text="company.inn || '—'"></span></div>
                        <div><span class="text-gray-400">Тел:</span> <span x-text="company.phone || '—'"></span></div>
                    </div>
                    <p class="native-caption mt-2" x-show="company.address" x-text="company.address"></p>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <button @click.stop="deleteCompany(company)" class="text-sm text-red-600">Удалить</button>
                    </div>
                </div>
            </template>
        </div>
    </main>

    
    <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl w-full max-h-[90vh] overflow-hidden" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="p-5 border-b border-gray-100">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="text-lg font-bold" x-text="editingCompany ? 'Редактировать' : 'Новая компания'"></h3>
            </div>
            <div class="p-5 overflow-y-auto max-h-[60vh] space-y-3">
                <input type="text" class="native-input w-full" x-model="form.name" placeholder="Название компании *">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" class="native-input" x-model="form.inn" placeholder="ИНН">
                    <input type="text" class="native-input" x-model="form.kpp" placeholder="КПП">
                </div>
                <input type="text" class="native-input w-full" x-model="form.ogrn" placeholder="ОГРН">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" class="native-input" x-model="form.phone" placeholder="Телефон">
                    <input type="email" class="native-input" x-model="form.email" placeholder="Email">
                </div>
                <textarea class="native-input w-full" rows="2" x-model="form.address" placeholder="Адрес"></textarea>
            </div>
            <div class="p-5 border-t border-gray-100 flex gap-2">
                <button @click="saveCompany()" class="native-btn native-btn-primary flex-1">Сохранить</button>
                <button @click="closeModal()" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\companies\index.blade.php ENDPATH**/ ?>