{{-- Bottom/Top Tab Navigation --}}
{{-- Desktop: horizontal scrollable dock with all items and labels --}}
{{-- PWA: 5 bottom tabs + full "More" bottom-sheet --}}

<nav x-data="dockNav()"
     x-show="shouldShow"
     x-cloak
     class="fixed left-0 right-0 z-50 flex justify-center"
     :class="{
         'bottom-0': position === 'bottom',
         'top-0': position === 'top'
     }"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">

    {{-- ============================================================ --}}
    {{-- DESKTOP DOCK: Horizontal scrollable bar with ALL items       --}}
    {{-- ============================================================ --}}
    <div x-show="!isPWA" class="w-full px-4 py-2">
        <div class="mx-auto max-w-fit rounded-2xl border border-white/30 px-4 py-2"
             style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.5);">
            <div class="flex items-center gap-1 overflow-x-auto"
                 style="-ms-overflow-style: none; scrollbar-width: none;"
                 x-ref="dockScroll">

                {{-- === GROUP 1: Home === --}}
                <a href="/home"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/home') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/home')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.home') }}</span>
                    <span x-show="isActive('/home')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Divider --}}
                <div class="mx-1 h-8 w-px self-center" style="background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1), transparent);"></div>

                {{-- === GROUP 2: Warehouse (with flyout) === --}}
                <div class="relative" @click.outside="warehouseMenuOpen = false">
                    <button type="button"
                            @click="warehouseMenuOpen = !warehouseMenuOpen"
                            class="group relative flex flex-col items-center justify-end px-2 py-1 transition-all duration-150"
                            :class="isWarehouseActive() ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                            style="min-width: 52px;">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                             :class="isWarehouseActive()
                                ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                                : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="mt-1 flex items-center gap-0.5 text-center text-[10px] font-medium leading-tight whitespace-nowrap">
                            {{ __('admin.warehouse_documents') }}
                            <svg class="h-2.5 w-2.5 transition-transform" :class="warehouseMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                        <span x-show="isWarehouseActive()" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                    </button>

                    {{-- Warehouse Flyout Popup --}}
                    <div x-show="warehouseMenuOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-1/2 z-50 w-56 -translate-x-1/2 rounded-2xl border border-white/30 bg-white py-2 shadow-xl"
                         :class="position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2'"
                         style="backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
                        <a href="/warehouse" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath === '/warehouse' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath === '/warehouse' ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_dashboard') }}
                        </a>
                        <a href="/warehouse/balance" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/balance') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/balance') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_balance') }}
                        </a>
                        <a href="/warehouse/in" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/in') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/in') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_receipt') }}
                        </a>
                        <a href="/warehouse/list" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/list') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/list') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_list') }}
                        </a>
                        <a href="/warehouse/documents" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/documents') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/documents') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_docs') }}
                        </a>
                        <a href="/warehouse/reservations" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/reservations') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/reservations') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_reservations') }}
                        </a>
                        <a href="/warehouse/write-off" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/write-off') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/write-off') ? 'bg-red-500' : 'bg-gray-300'"></span>
                            {{ __('warehouse.write_off') }}
                        </a>
                        <a href="/warehouse/ledger" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/warehouse/ledger') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/warehouse/ledger') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.warehouse_ledger') }}
                        </a>
                        <div class="mx-3 my-1 h-px bg-gray-100"></div>
                        <a href="/products" @click="warehouseMenuOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
                           :class="currentPath.startsWith('/products') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="currentPath.startsWith('/products') ? 'bg-blue-600' : 'bg-gray-300'"></span>
                            {{ __('admin.products') }}
                        </a>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="mx-1 h-8 w-px self-center" style="background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1), transparent);"></div>

                {{-- === GROUP 3: Main sections === --}}

                {{-- Marketplace --}}
                <a href="/marketplace"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/marketplace') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/marketplace')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.marketplace') }}</span>
                    <span x-show="isActive('/marketplace')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Sales --}}
                <a href="/sales"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/sales') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/sales')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.sales') }}</span>
                    <span x-show="isActive('/sales')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Counterparties --}}
                <a href="/counterparties"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/counterparties') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/counterparties')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.counterparties') }}</span>
                    <span x-show="isActive('/counterparties')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Inventory --}}
                <a href="/inventory"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/inventory') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/inventory')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.inventory') }}</span>
                    <span x-show="isActive('/inventory')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Sync Logs --}}
                <a href="/marketplace/sync-logs"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/marketplace/sync-logs') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/marketplace/sync-logs')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.sync_logs') }}</span>
                    <span x-show="isActive('/marketplace/sync-logs')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Tasks --}}
                <a href="/tasks"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/tasks') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/tasks')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.tasks') }}</span>
                    <span x-show="isActive('/tasks')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Replenishment / Planning --}}
                <a href="/replenishment"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/replenishment') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/replenishment')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.planning') }}</span>
                    <span x-show="isActive('/replenishment')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Divider --}}
                <div class="mx-1 h-8 w-px self-center" style="background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1), transparent);"></div>

                {{-- === GROUP 4: Finance === --}}

                {{-- Finance --}}
                <a href="/finance"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/finance') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/finance')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.finance') }}</span>
                    <span x-show="isActive('/finance')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Debts --}}
                <a href="/debts"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/debts') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/debts')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.debts') }}</span>
                    <span x-show="isActive('/debts')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Accounts Payable --}}
                <a href="/ap"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/ap') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/ap')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.accounts_payable') }}</span>
                    <span x-show="isActive('/ap')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Pricing --}}
                <a href="/pricing"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/pricing') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/pricing')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.pricing') }}</span>
                    <span x-show="isActive('/pricing')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Divider --}}
                <div class="mx-1 h-8 w-px self-center" style="background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1), transparent);"></div>

                {{-- === GROUP 5: System === --}}

                {{-- Integrations --}}
                <a href="/integrations"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/integrations') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/integrations')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.integrations') }}</span>
                    <span x-show="isActive('/integrations')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Company Profile --}}
                <a href="/company/profile"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/company') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/company')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('admin.company_profile') }}</span>
                    <span x-show="isActive('/company')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>

                {{-- Settings --}}
                <a href="/settings"
                   class="group relative flex flex-col items-center justify-end px-2 py-1 no-underline transition-all duration-150"
                   :class="isActive('/settings') ? 'text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                   style="min-width: 52px;">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150"
                         :class="isActive('/settings')
                            ? 'bg-gradient-to-br from-blue-100 to-blue-200 shadow-md shadow-blue-200/50'
                            : 'bg-gradient-to-br from-gray-50 to-gray-200 shadow-sm group-hover:from-blue-50 group-hover:to-blue-100'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="mt-1 text-center text-[10px] font-medium leading-tight whitespace-nowrap">{{ __('app.settings.title') }}</span>
                    <span x-show="isActive('/settings')" class="absolute -bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-blue-600"></span>
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PWA COMPACT MODE: 5 tabs + full "More" bottom-sheet          --}}
    {{-- ============================================================ --}}
    <div x-show="isPWA" class="w-full">

        {{-- More Menu Overlay (backdrop) --}}
        <div x-show="moreMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="moreMenuOpen = false"
             class="fixed inset-0 z-40 bg-black/30"
             style="backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);">
        </div>

        {{-- More Menu Bottom Sheet --}}
        <div x-show="moreMenuOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-full"
             class="fixed bottom-0 left-0 right-0 z-50 max-h-[80vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl"
             style="padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px));">

            {{-- Handle bar --}}
            <div class="sticky top-0 z-10 flex justify-center bg-white pt-3 pb-2">
                <div class="h-1 w-10 rounded-full bg-gray-300"></div>
            </div>

            <div class="px-4 pb-4">

                {{-- WAREHOUSE group --}}
                <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('admin.warehouse_section') }}</p>
                <div class="mb-4 space-y-0.5">
                    <a href="/warehouse" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath === '/warehouse' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        {{ __('admin.warehouse_dashboard') }}
                    </a>
                    <a href="/warehouse/balance" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/balance') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3v-6m-3 6v-1m6-9a2 2 0 012 2v8a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2"/>
                        </svg>
                        {{ __('admin.warehouse_balance') }}
                    </a>
                    <a href="/warehouse/in" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/in') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        {{ __('admin.warehouse_receipt') }}
                    </a>
                    <a href="/warehouse/list" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/list') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                        {{ __('admin.warehouse_list') }}
                    </a>
                    <a href="/warehouse/documents" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/documents') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('admin.warehouse_docs') }}
                    </a>
                    <a href="/warehouse/reservations" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/reservations') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        {{ __('admin.warehouse_reservations') }}
                    </a>
                    <a href="/warehouse/write-off" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/write-off') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('warehouse.write_off') }}
                    </a>
                    <a href="/warehouse/ledger" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/warehouse/ledger') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        {{ __('admin.warehouse_ledger') }}
                    </a>
                    <a href="/products" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/products') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        {{ __('admin.products') }}
                    </a>
                </div>

                {{-- MAIN group --}}
                <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('admin.main_navigation') }}</p>
                <div class="mb-4 space-y-0.5">
                    <a href="/counterparties" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/counterparties') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        {{ __('admin.counterparties') }}
                    </a>
                    <a href="/inventory" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/inventory') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        {{ __('admin.inventory') }}
                    </a>
                    <a href="/marketplace/sync-logs" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/marketplace/sync-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('admin.sync_logs') }}
                    </a>
                    <a href="/tasks" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/tasks') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        {{ __('admin.tasks') }}
                    </a>
                    <a href="/replenishment" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/replenishment') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('admin.planning') }}
                    </a>
                </div>

                {{-- FINANCE group --}}
                <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('admin.finance') }}</p>
                <div class="mb-4 space-y-0.5">
                    <a href="/finance" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/finance') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('admin.finance') }}
                    </a>
                    <a href="/debts" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/debts') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                        {{ __('admin.debts') }}
                    </a>
                    <a href="/ap" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/ap') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('admin.accounts_payable') }}
                    </a>
                    <a href="/pricing" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/pricing') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ __('admin.pricing') }}
                    </a>
                </div>

                {{-- OTHER group --}}
                <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('app.settings.title') }}</p>
                <div class="space-y-0.5">
                    <a href="/integrations" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/integrations') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                        {{ __('admin.integrations') }}
                    </a>
                    <a href="/company/profile" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/company') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ __('admin.company_profile') }}
                    </a>
                    <a href="/settings" @click="moreMenuOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                       :class="currentPath.startsWith('/settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ __('app.settings.title') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- 5 Tab Bar --}}
        <div class="relative z-50 flex items-center justify-around border-t border-gray-200/60 bg-white/98"
             style="height: 56px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">

            {{-- Home --}}
            <a href="/home"
               class="flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
               :class="isActive('/home') ? 'text-blue-600' : 'text-gray-400'"
               style="-webkit-tap-highlight-color: transparent;">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="mt-0.5 text-[10px] font-medium">{{ __('admin.home') }}</span>
            </a>

            {{-- Warehouse --}}
            <a href="/warehouse"
               class="flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
               :class="isWarehouseActive() ? 'text-blue-600' : 'text-gray-400'"
               style="-webkit-tap-highlight-color: transparent;">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="mt-0.5 text-[10px] font-medium">{{ __('admin.warehouse_documents') }}</span>
            </a>

            {{-- Marketplace --}}
            <a href="/marketplace"
               class="flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
               :class="isActive('/marketplace') && !isActive('/marketplace/sync-logs') ? 'text-blue-600' : 'text-gray-400'"
               style="-webkit-tap-highlight-color: transparent;">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="mt-0.5 text-[10px] font-medium">{{ __('admin.marketplace') }}</span>
            </a>

            {{-- Sales --}}
            <a href="/sales"
               class="flex flex-1 flex-col items-center justify-center py-1 no-underline transition-colors duration-150"
               :class="isActive('/sales') ? 'text-blue-600' : 'text-gray-400'"
               style="-webkit-tap-highlight-color: transparent;">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="mt-0.5 text-[10px] font-medium">{{ __('admin.sales') }}</span>
            </a>

            {{-- More --}}
            <button type="button"
                    @click="moreMenuOpen = !moreMenuOpen"
                    class="flex flex-1 flex-col items-center justify-center py-1 transition-colors duration-150"
                    :class="moreMenuOpen || isMoreActive() ? 'text-blue-600' : 'text-gray-400'"
                    style="-webkit-tap-highlight-color: transparent;">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                </svg>
                <span class="mt-0.5 text-[10px] font-medium">{{ __('app.settings.navigation.more') }}</span>
            </button>
        </div>
    </div>
</nav>

<style>
/* Hide scrollbar for desktop dock */
[x-ref="dockScroll"]::-webkit-scrollbar {
    display: none;
}
</style>

<script>
function dockNav() {
    return {
        currentPath: window.location.pathname,
        moreMenuOpen: false,
        warehouseMenuOpen: false,
        isPWA: false,
        position: 'left',

        init() {
            this.isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone ||
                         document.referrer.includes('android-app://');

            if (typeof Alpine !== 'undefined' && Alpine.store('ui')) {
                this.position = Alpine.store('ui').navPosition || 'left';
            }

            this.$watch('$store.ui.navPosition', (val) => {
                this.position = val;
            });

            window.addEventListener('popstate', () => {
                this.currentPath = window.location.pathname;
            });
        },

        get shouldShow() {
            if (typeof Alpine !== 'undefined' && Alpine.store('ui')) {
                const navPosition = Alpine.store('ui').navPosition;
                return this.isPWA || navPosition === 'bottom' || navPosition === 'top';
            }
            return this.isPWA;
        },

        isActive(path) {
            if (path === '/home' || path === '/dashboard') {
                return this.currentPath === '/home' || this.currentPath === '/dashboard' || this.currentPath === '/';
            }
            if (path === '/marketplace/sync-logs') {
                return this.currentPath.startsWith('/marketplace/sync-logs');
            }
            if (path === '/marketplace') {
                return this.currentPath === '/marketplace' || (this.currentPath.startsWith('/marketplace') && !this.currentPath.startsWith('/marketplace/sync-logs'));
            }
            return this.currentPath.startsWith(path);
        },

        isWarehouseActive() {
            return this.currentPath.startsWith('/warehouse') || this.currentPath.startsWith('/products');
        },

        isMoreActive() {
            const morePaths = [
                '/warehouse', '/products',
                '/counterparties', '/inventory', '/marketplace/sync-logs', '/tasks', '/replenishment',
                '/finance', '/debts', '/ap', '/pricing',
                '/integrations', '/company', '/settings'
            ];
            return morePaths.some(path => this.currentPath.startsWith(path));
        }
    };
}
</script>
