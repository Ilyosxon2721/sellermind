@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div x-data="suppliesManager({{ $accountId }})" x-init="init()" class="browser-only container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Управление поставками</h1>
            <p class="text-sm text-gray-600 mt-1">Создание и управление FBS поставками</p>
        </div>
        <button @click="showCreateModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать поставку
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Всего поставок</div>
            <div class="text-2xl font-bold text-gray-800" x-text="supplies.length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Активные</div>
            <div class="text-2xl font-bold text-blue-600" x-text="activeSuppliesCount"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Доставлено</div>
            <div class="text-2xl font-bold text-green-600" x-text="deliveredSuppliesCount"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Заказов в поставках</div>
            <div class="text-2xl font-bold text-purple-600" x-text="totalOrdersCount"></div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">Загрузка поставок...</p>
    </div>

    <!-- Supplies List -->
    <div x-show="!loading" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказов</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Создана</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="supply in supplies" :key="supply.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="supply.id"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="supply.name"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-gray-100 text-gray-800': supply.status === 'draft',
                                'bg-blue-100 text-blue-800': supply.status === 'in_assembly',
                                'bg-green-100 text-green-800': supply.status === 'ready',
                                'bg-purple-100 text-purple-800': supply.status === 'sent',
                                'bg-emerald-100 text-emerald-800': supply.status === 'delivered',
                                'bg-red-100 text-red-800': supply.status === 'cancelled'
                            }" class="px-2 py-1 text-xs font-semibold rounded-full" x-text="getStatusText(supply.status)"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="supply.orders_count || 0"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(supply.created_at)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <button @click="viewSupply(supply)" class="text-blue-600 hover:text-blue-800">Открыть</button>
                            <button x-show="!supply.external_supply_id || !supply.external_supply_id.startsWith('WB-')" @click="syncWithWb(supply.id)" class="text-indigo-600 hover:text-indigo-800">Sync WB</button>
                            <button x-show="supply.external_supply_id && supply.external_supply_id.startsWith('WB-')" @click="downloadBarcode(supply.id)" class="text-green-600 hover:text-green-800">QR</button>
                            <button x-show="!supply.closed_at" @click="closeSupply(supply.id)" class="text-amber-600 hover:text-amber-800">Закрыть</button>
                            <button x-show="supply.status === 'ready'" @click="markAsSent(supply.id)" class="text-purple-600 hover:text-purple-800">Отправить</button>
                            <button x-show="supply.orders_count === 0"
                                    @click="deleteSupply(supply.id)"
                                    :disabled="deletingSupplyId === supply.id"
                                    class="text-red-600 hover:text-red-800 disabled:opacity-50 flex items-center">
                                <svg x-show="deletingSupplyId === supply.id" class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="deletingSupplyId === supply.id ? 'Удаление...' : 'Удалить'"></span>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Empty State -->
        <div x-show="supplies.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Нет поставок</h3>
            <p class="mt-1 text-sm text-gray-500">Создайте первую поставку для отправки заказов</p>
        </div>
    </div>

    <!-- Create Supply Modal -->
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div @click.away="showCreateModal = false" class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">Создать поставку</h3>
            <input type="text" x-model="newSupplyName" placeholder="Название поставки"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4">
            <div class="flex gap-2">
                <button @click="createSupply()" :disabled="creating" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg disabled:opacity-50 flex items-center justify-center">
                    <svg x-show="creating" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="creating ? 'Создание...' : 'Создать'"></span>
                </button>
                <button @click="showCreateModal = false" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg">Отмена</button>
            </div>
        </div>
    </div>

    <!-- View Supply Modal -->
    <div x-show="showSupplyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto" x-cloak>
        <div @click.away="showSupplyModal = false" class="bg-white rounded-lg p-6 max-w-4xl w-full my-8 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Поставка: <span x-text="selectedSupply?.name"></span></h3>
                <button @click="showSupplyModal = false" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>

            <div class="mb-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><span class="font-semibold">ID:</span> <span x-text="selectedSupply?.id"></span></div>
                    <div><span class="font-semibold">Статус:</span> <span x-text="getStatusText(selectedSupply?.status)"></span></div>
                    <div><span class="font-semibold">Заказов:</span> <span x-text="selectedSupply?.orders_count || 0"></span></div>
                    <div><span class="font-semibold">Создана:</span> <span x-text="formatDate(selectedSupply?.created_at)"></span></div>
                    <div><span class="font-semibold">Общая сумма:</span> <span x-text="formatPrice(selectedSupply?.total_amount || 0)"></span></div>
                    <div x-show="selectedSupply?.description"><span class="font-semibold">Описание:</span> <span x-text="selectedSupply?.description"></span></div>
                </div>
            </div>

            <!-- Supply Orders -->
            <div class="border-t pt-4">
                <h4 class="font-semibold mb-2">Заказы в поставке</h4>
                <div class="max-h-96 overflow-y-auto">
                    <template x-for="order in supplyOrders" :key="order.id">
                        <div class="border rounded-lg p-3 mb-2 flex justify-between items-center hover:bg-gray-50">
                            <div class="flex-1">
                                <div class="font-medium">Заказ #<span x-text="order.external_order_id"></span></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-show="order.wb_article">Артикул: <span x-text="order.wb_article"></span></span>
                                    <span x-show="order.wb_nm_id" class="ml-3">NM ID: <span x-text="order.wb_nm_id"></span></span>
                                </div>
                                <div class="text-sm font-semibold text-gray-900 mt-1" x-text="formatPrice(order.wb_final_price)"></div>
                            </div>
                            <button @click="removeOrderFromSupply(order.id)"
                                    :disabled="removingOrderId === order.id"
                                    class="ml-4 px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs rounded-lg transition disabled:opacity-50 flex items-center">
                                <svg x-show="removingOrderId === order.id" class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="removingOrderId === order.id ? 'Удаление...' : 'Удалить'"></span>
                            </button>
                        </div>
                    </template>
                    <div x-show="supplyOrders.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p>Нет заказов в поставке</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function suppliesManager(accountId) {
    return {
        accountId: accountId,
        supplies: [],
        loading: true,
        creating: false,
        deletingSupplyId: null,
        removingOrderId: null,
        showCreateModal: false,
        showSupplyModal: false,
        newSupplyName: '',
        selectedSupply: null,
        supplyOrders: [],

        get activeSuppliesCount() {
            return this.supplies.filter(s => s.status === 'draft' || s.status === 'in_assembly').length;
        },

        get deliveredSuppliesCount() {
            return this.supplies.filter(s => s.status === 'delivered').length;
        },

        get totalOrdersCount() {
            return this.supplies.reduce((sum, s) => sum + (s.orders_count || 0), 0);
        },

        async init() {
            await this.loadSupplies();
        },

        getAuthHeaders() {
            const token = this.$store.auth.token || localStorage.getItem('auth_token');
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        },

        async loadSupplies() {
            this.loading = true;
            try {
                const response = await axios.get('/api/marketplace/supplies', {
                    headers: this.getAuthHeaders(),
                    params: {
                        company_id: this.$store.auth.currentCompany.id,
                        marketplace_account_id: this.accountId
                    }
                });
                this.supplies = response.data.supplies || [];
            } catch (error) {
                console.error('Failed to load supplies:', error);
                alert('Ошибка загрузки поставок');
            } finally {
                this.loading = false;
            }
        },

        async createSupply() {
            if (!this.newSupplyName.trim()) {
                alert('Введите название поставки');
                return;
            }

            this.creating = true;
            try {
                const response = await axios.post('/api/marketplace/supplies', {
                    marketplace_account_id: this.accountId,
                    company_id: this.$store.auth.currentCompany.id,
                    name: this.newSupplyName
                }, {
                    headers: this.getAuthHeaders()
                });

                if (response.data.supply) {
                    this.showCreateModal = false;
                    this.newSupplyName = '';
                    await this.loadSupplies();
                    alert('Поставка успешно создана');
                }
            } catch (error) {
                console.error('Failed to create supply:', error);
                alert(error.response?.data?.message || 'Ошибка создания поставки');
            } finally {
                this.creating = false;
            }
        },

        async viewSupply(supply) {
            this.selectedSupply = supply;
            this.showSupplyModal = true;

            try {
                const response = await axios.get(`/api/marketplace/supplies/${supply.id}`, {
                    headers: this.getAuthHeaders()
                });
                this.supplyOrders = response.data.supply.orders || [];
            } catch (error) {
                console.error('Failed to load supply orders:', error);
            }
        },

        async closeSupply(supplyId) {
            if (!confirm('Закрыть поставку для добавления заказов?')) return;

            try {
                const response = await axios.post(`/api/marketplace/supplies/${supplyId}/close`, {}, {
                    headers: this.getAuthHeaders()
                });

                if (response.data.supply) {
                    await this.loadSupplies();
                    alert('Поставка закрыта');
                }
            } catch (error) {
                console.error('Failed to close supply:', error);
                alert(error.response?.data?.message || 'Ошибка при закрытии поставки');
            }
        },

        async markAsSent(supplyId) {
            if (!confirm('Отметить поставку как отправленную?')) return;

            try {
                const response = await axios.put(`/api/marketplace/supplies/${supplyId}`, {
                    status: 'sent'
                }, {
                    headers: this.getAuthHeaders()
                });

                if (response.data.supply) {
                    await this.loadSupplies();
                    alert('Поставка отмечена как отправленная');
                }
            } catch (error) {
                console.error('Failed to mark supply as sent:', error);
                alert(error.response?.data?.message || 'Ошибка при отправке поставки');
            }
        },

        async deleteSupply(supplyId) {
            if (!confirm('Удалить поставку? Это действие нельзя отменить.')) return;

            this.deletingSupplyId = supplyId;
            try {
                await axios.delete(`/api/marketplace/supplies/${supplyId}`, {
                    headers: this.getAuthHeaders()
                });
                await this.loadSupplies();
                alert('Поставка удалена');
            } catch (error) {
                console.error('Failed to delete supply:', error);
                alert(error.response?.data?.message || 'Ошибка при удалении поставки');
            } finally {
                this.deletingSupplyId = null;
            }
        },

        async removeOrderFromSupply(orderId) {
            if (!confirm('Удалить заказ из поставки?')) return;

            this.removingOrderId = orderId;
            try {
                const response = await axios.delete(`/api/marketplace/supplies/${this.selectedSupply.id}/orders`, {
                    headers: this.getAuthHeaders(),
                    data: { order_id: orderId }
                });

                if (response.data.supply) {
                    await this.viewSupply(this.selectedSupply);
                    alert('Заказ удалён из поставки');
                }
            } catch (error) {
                console.error('Failed to remove order:', error);
                alert(error.response?.data?.message || 'Ошибка удаления заказа');
            } finally {
                this.removingOrderId = null;
            }
        },

        async syncWithWb(supplyId) {
            if (!confirm('Создать поставку в Wildberries? После этого вы сможете скачать баркод.')) return;

            try {
                const response = await axios.post(`/api/marketplace/supplies/${supplyId}/sync-wb`, {}, {
                    headers: this.getAuthHeaders()
                });

                if (response.data.supply) {
                    await this.loadSupplies();
                    alert('Поставка успешно синхронизирована с Wildberries! Теперь вы можете скачать баркод.');
                }
            } catch (error) {
                console.error('Failed to sync with WB:', error);
                alert(error.response?.data?.message || 'Ошибка синхронизации с WB');
            }
        },

        async downloadBarcode(supplyId) {
            try {
                const response = await axios.get(`/api/marketplace/supplies/${supplyId}/barcode`, {
                    headers: this.getAuthHeaders(),
                    params: { type: 'png' },
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `supply-${supplyId}-barcode.png`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Failed to download barcode:', error);
                alert(error.response?.data?.message || 'Ошибка скачивания баркода');
            }
        },

        getStatusText(status) {
            const statuses = {
                'draft': 'Черновик',
                'in_assembly': 'На сборке',
                'ready': 'Готова',
                'sent': 'Отправлена',
                'delivered': 'Доставлена',
                'cancelled': 'Отменена'
            };
            return statuses[status] || status;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('ru-RU');
        },

        formatPrice(price) {
            if (!price) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="suppliesManager({{ $accountId }})" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Поставки" :backUrl="'/marketplace/wb/' . $accountId">
        <button @click="showCreateModal = true" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            + Создать
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadSupplies">

        {{-- Stats --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-gray-800" x-text="supplies.length">0</p>
                <p class="native-caption">Всего</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-blue-600" x-text="activeSuppliesCount">0</p>
                <p class="native-caption">Активных</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-green-600" x-text="deliveredSuppliesCount">0</p>
                <p class="native-caption">Доставлено</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-purple-600" x-text="totalOrdersCount">0</p>
                <p class="native-caption">Заказов</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="4" />
        </div>

        {{-- Supplies List --}}
        <div x-show="!loading" class="px-4 space-y-3">
            <template x-for="supply in supplies" :key="supply.id">
                <div class="native-card" @click="viewSupply(supply)">
                    <div class="flex items-center justify-between mb-2">
                        <p class="native-body font-semibold" x-text="supply.name"></p>
                        <span :class="{
                            'bg-gray-100 text-gray-800': supply.status === 'draft',
                            'bg-blue-100 text-blue-800': supply.status === 'in_assembly',
                            'bg-green-100 text-green-800': supply.status === 'ready',
                            'bg-purple-100 text-purple-800': supply.status === 'sent',
                            'bg-emerald-100 text-emerald-800': supply.status === 'delivered',
                            'bg-red-100 text-red-800': supply.status === 'cancelled'
                        }" class="px-2 py-0.5 text-xs font-medium rounded-full" x-text="getStatusText(supply.status)"></span>
                    </div>
                    <div class="native-caption">
                        <span class="text-gray-400">ID:</span> <span x-text="supply.id"></span>
                        <span class="mx-2">•</span>
                        <span x-text="(supply.orders_count || 0) + ' заказов'"></span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-2">
                        <button x-show="!supply.external_supply_id || !supply.external_supply_id.startsWith('WB-')"
                                @click.stop="syncWithWb(supply.id)" class="text-xs text-indigo-600 px-2 py-1 bg-indigo-50 rounded-lg">Sync WB</button>
                        <button x-show="supply.external_supply_id && supply.external_supply_id.startsWith('WB-')"
                                @click.stop="downloadBarcode(supply.id)" class="text-xs text-green-600 px-2 py-1 bg-green-50 rounded-lg">QR</button>
                        <button x-show="!supply.closed_at"
                                @click.stop="closeSupply(supply.id)" class="text-xs text-amber-600 px-2 py-1 bg-amber-50 rounded-lg">Закрыть</button>
                        <button x-show="supply.status === 'ready'"
                                @click.stop="markAsSent(supply.id)" class="text-xs text-purple-600 px-2 py-1 bg-purple-50 rounded-lg">Отправить</button>
                        <button x-show="supply.orders_count === 0"
                                @click.stop="deleteSupply(supply.id)"
                                :disabled="deletingSupplyId === supply.id"
                                class="text-xs text-red-600 px-2 py-1 bg-red-50 rounded-lg disabled:opacity-50">
                                <span x-text="deletingSupplyId === supply.id ? '...' : 'Удалить'"></span>
                            </button>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="supplies.length === 0 && !loading" class="text-center py-12 native-caption">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p>Нет поставок</p>
                <button @click="showCreateModal = true" class="native-btn native-btn-primary mt-4">
                    Создать поставку
                </button>
            </div>
        </div>
    </main>

    {{-- Create Modal --}}
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div @click.away="showCreateModal = false" class="bg-white rounded-t-2xl p-5 w-full max-w-md" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-bold mb-4">Создать поставку</h3>
            <input type="text" x-model="newSupplyName" placeholder="Название поставки" class="native-input w-full mb-4">
            <div class="flex gap-2">
                <button @click="createSupply()" :disabled="creating" class="native-btn native-btn-primary flex-1 disabled:opacity-50 flex items-center justify-center">
                    <svg x-show="creating" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="creating ? 'Создание...' : 'Создать'"></span>
                </button>
                <button @click="showCreateModal = false" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>

    {{-- View Supply Modal --}}
    <div x-show="showSupplyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div @click.away="showSupplyModal = false" class="bg-white rounded-t-2xl w-full max-h-[80vh] overflow-hidden" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="p-5 border-b border-gray-100">
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold" x-text="selectedSupply?.name"></h3>
                    <span :class="{
                        'bg-gray-100 text-gray-800': selectedSupply?.status === 'draft',
                        'bg-blue-100 text-blue-800': selectedSupply?.status === 'in_assembly',
                        'bg-green-100 text-green-800': selectedSupply?.status === 'ready',
                        'bg-purple-100 text-purple-800': selectedSupply?.status === 'sent',
                        'bg-emerald-100 text-emerald-800': selectedSupply?.status === 'delivered'
                    }" class="px-2 py-0.5 text-xs font-medium rounded-full" x-text="getStatusText(selectedSupply?.status)"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3 native-caption">
                    <div><span class="text-gray-400">ID:</span> <span x-text="selectedSupply?.id"></span></div>
                    <div><span class="text-gray-400">Заказов:</span> <span x-text="selectedSupply?.orders_count || 0"></span></div>
                    <div><span class="text-gray-400">Сумма:</span> <span x-text="formatPrice(selectedSupply?.total_amount || 0)"></span></div>
                </div>
            </div>
            <div class="p-5 overflow-y-auto max-h-[50vh]">
                <h4 class="font-semibold mb-3">Заказы</h4>
                <div class="space-y-2">
                    <template x-for="order in supplyOrders" :key="order.id">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="native-body font-medium">#<span x-text="order.external_order_id"></span></p>
                                    <p class="native-caption mt-1" x-show="order.wb_article">Артикул: <span x-text="order.wb_article"></span></p>
                                </div>
                                <p class="native-body font-semibold" x-text="formatPrice(order.wb_final_price)"></p>
                            </div>
                            <button @click="removeOrderFromSupply(order.id)" class="mt-2 text-xs text-red-600">Удалить</button>
                        </div>
                    </template>
                    <div x-show="supplyOrders.length === 0" class="text-center py-6 native-caption">
                        Нет заказов в поставке
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
