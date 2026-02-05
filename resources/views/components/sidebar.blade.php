<!-- Sidebar Component -->
<div class="bg-white flex flex-col h-full transition-all duration-300
            max-lg:fixed max-lg:inset-y-0 max-lg:z-40"
     :class="[
         $store.ui.navPosition === 'right' ? 'right-0 border-l border-gray-200' : 'left-0 border-r border-gray-200',
         sidebarOpen ? '' : ($store.ui.navPosition === 'right' ? 'max-lg:translate-x-full' : 'max-lg:-translate-x-full'),
         $store.ui.sidebarCollapsed ? 'w-16' : 'w-72'
     ]"
     role="complementary">
    <!-- Logo - Fixed at top -->
    <div class="p-4 border-b border-gray-200 flex-shrink-0">
        <a href="/home" class="flex items-center" :class="$store.ui.sidebarCollapsed ? 'justify-center' : 'space-x-3'" aria-label="SellerMind">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div x-show="!$store.ui.sidebarCollapsed" x-transition.opacity class="overflow-hidden">
                <h1 class="font-bold text-gray-900">SellerMind</h1>
                <p class="text-xs text-gray-500 truncate" x-text="$store.auth.currentCompany?.name || '{{ __('admin.select_company') }}'"></p>
            </div>
        </a>
    </div>

    <!-- Navigation - Scrollable middle section -->
    <nav class="flex-1 overflow-y-auto" :class="$store.ui.sidebarCollapsed ? 'p-2 space-y-1' : 'p-4 space-y-1'" role="navigation" aria-label="{{ __('admin.main_navigation') }}">
        {{-- Home --}}
        <a href="/home"
           class="flex items-center rounded-lg transition {{ request()->is('home') || request()->is('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.home') }}' : ''"
           aria-label="{{ __('admin.home') }}"
           {{ request()->is('home') || request()->is('dashboard') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.home') }}</span>
        </a>

        {{-- Warehouse Section Header --}}
        <div x-show="!$store.ui.sidebarCollapsed" class="px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('admin.warehouse_section') }}</div>

        {{-- Warehouse Menu - Collapsed: single icon, Expanded: collapsible group --}}
        <div x-data="{open: {{ request()->is('warehouse*') || request()->is('products*') ? 'true' : 'false' }}}">
            {{-- Expanded mode: show collapsible group --}}
            <template x-if="!$store.ui.sidebarCollapsed">
                <div>
                    <button type="button"
                            class="flex items-center justify-between w-full px-3 py-2 rounded-lg transition text-gray-700 hover:bg-gray-100"
                            @click="open = !open"
                            aria-label="{{ __('admin.warehouse_menu') }}"
                            :aria-expanded="open.toString()">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="font-medium">{{ __('admin.warehouse_documents') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-500 transform transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="ml-6 space-y-1" x-show="open" x-cloak x-transition>
                        <a href="/warehouse" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_dashboard') }}</span>
                        </a>
                        <a href="/warehouse/balance" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/balance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_balance') }}</span>
                        </a>
                        <a href="/warehouse/in" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/in*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_receipt') }}</span>
                        </a>
                        <a href="/warehouse/list" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/list*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_list') }}</span>
                        </a>
                        <a href="/warehouse/documents" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/documents*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_docs') }}</span>
                        </a>
                        <a href="/warehouse/reservations" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/reservations*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_reservations') }}</span>
                        </a>
                        <a href="/warehouse/write-off" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/write-off*') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs text-red-500">•</span>
                            <span class="text-sm">{{ __('warehouse.write_off') }}</span>
                        </a>
                        <a href="/warehouse/ledger" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/ledger*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.warehouse_ledger') }}</span>
                        </a>
                        <a href="/products" class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('products*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="text-xs">•</span>
                            <span class="text-sm">{{ __('admin.products') }}</span>
                        </a>
                    </div>
                </div>
            </template>
            {{-- Collapsed mode: just show warehouse icon --}}
            <template x-if="$store.ui.sidebarCollapsed">
                <a href="/warehouse"
                   class="flex items-center justify-center p-2.5 rounded-lg transition {{ request()->is('warehouse*') || request()->is('products*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   title="{{ __('admin.warehouse_documents') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </a>
            </template>
        </div>

        {{-- Marketplace --}}
        <a href="/marketplace"
           class="flex items-center rounded-lg transition {{ request()->is('marketplace') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.marketplace') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.marketplace') }}</span>
        </a>

        {{-- Sales --}}
        <a href="/sales"
           class="flex items-center rounded-lg transition {{ request()->is('sales*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.sales') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.sales') }}</span>
        </a>

        {{-- Counterparties --}}
        <a href="/counterparties"
           class="flex items-center rounded-lg transition {{ request()->is('counterparties*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.counterparties') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.counterparties') }}</span>
        </a>

        {{-- Inventory --}}
        <a href="/inventory"
           class="flex items-center rounded-lg transition {{ request()->is('inventory*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.inventory') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.inventory') }}</span>
        </a>

        {{-- Sync Logs --}}
        <a href="/marketplace/sync-logs"
           class="flex items-center rounded-lg transition {{ request()->is('marketplace/sync-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.sync_logs') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.sync_logs') }}</span>
        </a>

        {{-- Tasks --}}
        <a href="/tasks"
           class="flex items-center rounded-lg transition {{ request()->is('tasks*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.tasks') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.tasks') }}</span>
        </a>

        {{-- Replenishment --}}
        <a href="/replenishment"
           class="flex items-center rounded-lg transition {{ request()->is('replenishment*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.planning') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.planning') }}</span>
        </a>

        {{-- Finance --}}
        <a href="/finance"
           class="flex items-center rounded-lg transition {{ request()->is('finance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.finance') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.finance') }}</span>
        </a>

        {{-- Debts --}}
        <a href="/debts"
           class="flex items-center rounded-lg transition {{ request()->is('debts*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.debts') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.debts') }}</span>
        </a>

        {{-- Accounts Payable --}}
        <a href="/ap"
           class="flex items-center rounded-lg transition {{ request()->is('ap*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.accounts_payable') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.accounts_payable') }}</span>
        </a>

        {{-- Pricing --}}
        <a href="/pricing"
           class="flex items-center rounded-lg transition {{ request()->is('pricing*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.pricing') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.pricing') }}</span>
        </a>

        {{-- Integrations --}}
        <a href="/integrations"
           class="flex items-center rounded-lg transition {{ request()->is('integrations*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.integrations') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.integrations') }}</span>
        </a>

        <hr class="my-2 border-gray-200" x-show="!$store.ui.sidebarCollapsed">
        <div x-show="$store.ui.sidebarCollapsed" class="my-2 flex justify-center">
            <div class="w-6 h-px bg-gray-200"></div>
        </div>

        {{-- Company Profile --}}
        <a href="/company/profile"
           class="flex items-center rounded-lg transition {{ request()->is('company/*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('admin.company_profile') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('admin.company_profile') }}</span>
        </a>

        {{-- Settings --}}
        <a href="/settings"
           class="flex items-center rounded-lg transition {{ request()->is('settings*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           :class="$store.ui.sidebarCollapsed ? 'justify-center p-2.5' : 'space-x-3 px-3 py-2.5'"
           :title="$store.ui.sidebarCollapsed ? '{{ __('app.settings.title') }}' : ''">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span x-show="!$store.ui.sidebarCollapsed" class="font-medium">{{ __('app.settings.title') }}</span>
        </a>

        <!-- Slot for page-specific content -->
        @if(isset($slot) && $slot instanceof \Illuminate\Support\HtmlString && !empty(trim($slot)))
            <div class="border-t border-gray-200 pt-4 mt-4" x-show="!$store.ui.sidebarCollapsed">
                {{ $slot }}
            </div>
        @endif
    </nav>

    <!-- Collapse Toggle Button -->
    <div class="px-2 py-2 border-t border-gray-200 flex-shrink-0">
        <button @click="$store.ui.toggleSidebarCollapse()"
                class="w-full flex items-center justify-center p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
                :title="$store.ui.sidebarCollapsed ? '{{ __('app.settings.navigation.collapse') }}' : '{{ __('app.settings.navigation.collapse') }}'">
            <svg class="w-5 h-5 transition-transform" :class="$store.ui.sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <!-- Language Switcher - Fixed at bottom -->
    <div class="flex-shrink-0 border-t border-gray-200" :class="$store.ui.sidebarCollapsed ? 'px-2 py-2' : 'px-4 py-3'">
        <template x-if="!$store.ui.sidebarCollapsed">
            <div>
                @include('components.dashboard-language-switcher')
            </div>
        </template>
        <template x-if="$store.ui.sidebarCollapsed">
            <div class="flex justify-center">
                <a href="/settings?tab=language" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="{{ __('app.settings.tabs.language') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                </a>
            </div>
        </template>
    </div>

    <!-- User Menu - Fixed at bottom -->
    <div class="border-t border-gray-200 flex-shrink-0" :class="$store.ui.sidebarCollapsed ? 'p-2' : 'p-4'" role="region" aria-label="{{ __('admin.user_menu') }}">
        <div class="flex items-center" :class="$store.ui.sidebarCollapsed ? 'justify-center' : 'space-x-3'">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-medium flex-shrink-0"
                 x-text="($store.auth.user?.name || $store.auth.user?.email || '?')[0].toUpperCase()"
                 :title="$store.ui.sidebarCollapsed ? ($store.auth.user?.name || $store.auth.user?.email) : ''">
            </div>
            <div x-show="!$store.ui.sidebarCollapsed" class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate" x-text="$store.auth.user?.name || $store.auth.user?.email"></p>
            </div>
            <button x-show="!$store.ui.sidebarCollapsed"
                    @click="$store.auth.logout(); window.location.href='/login'"
                    class="text-gray-400 hover:text-gray-600"
                    aria-label="{{ __('admin.logout_button') }}"
                    title="{{ __('admin.logout') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </div>
    </div>
</div>
