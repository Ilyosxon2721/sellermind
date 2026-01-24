<!-- Sidebar Component -->
<div class="w-72 bg-white border-r border-gray-200 flex flex-col h-full"
     :class="sidebarOpen ? 'open' : ''"
     role="complementary">
    <!-- Logo - Fixed at top -->
    <div class="p-4 border-b border-gray-200 flex-shrink-0">
        <a href="/home" class="flex items-center space-x-3" aria-label="SellerMind - Главная страница">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <h1 class="font-bold text-gray-900">SellerMind</h1>
                <p class="text-xs text-gray-500" x-text="$store.auth.currentCompany?.name || '{{ __('admin.select_company') }}'"></p>
            </div>
        </a>
    </div>

    <!-- Navigation - Scrollable middle section -->
    <nav class="p-4 space-y-1 flex-1 overflow-y-auto" role="navigation" aria-label="{{ __('admin.main_navigation') }}">
        <a href="/home"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('home') || request()->is('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.home') }}"
           {{ request()->is('home') || request()->is('dashboard') ? 'aria-current="page"' : '' }}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-medium">{{ __('admin.home') }}</span>
        </a>

        {{-- Чат - временно скрыт до завершения разработки --}}

        <div class="px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('admin.warehouse_section') }}</div>
        <div x-data="{open: {{ request()->is('warehouse*') ? 'true' : 'false' }}}">
            <button type="button"
                    class="flex items-center justify-between w-full px-3 py-2 rounded-lg transition text-gray-700 hover:bg-gray-100"
                    @click="open = !open"
                    aria-label="{{ __('admin.warehouse_menu') }}"
                    :aria-expanded="open.toString()">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    <span class="font-medium">{{ __('admin.warehouse_documents') }}</span>
                </div>
                <svg class="w-4 h-4 text-gray-500 transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div class="ml-6 space-y-1" x-show="open" x-cloak>
                <a href="/warehouse"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_dashboard') }}</span>
                </a>
                <a href="/warehouse/balance"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/balance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/balance*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_balance') }}</span>
                </a>
                <a href="/warehouse/in"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/in*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/in*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_receipt') }}</span>
                </a>
                <a href="/warehouse/list"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/list*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/list*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_list') }}</span>
                </a>
                <a href="/warehouse/documents"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/documents*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/documents*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_docs') }}</span>
                </a>
                <a href="/warehouse/reservations"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/reservations*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/reservations*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_reservations') }}</span>
                </a>
                <a href="/warehouse/ledger"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/ledger*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('warehouse/ledger*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.warehouse_ledger') }}</span>
                </a>
                <a href="/products"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('products*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                   {{ request()->is('products*') ? 'aria-current="page"' : '' }}>
                    <span class="text-xs">•</span>
                    <span class="text-sm">{{ __('admin.products') }}</span>
                </a>
            </div>
        </div>

        <a href="/marketplace"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('marketplace') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.marketplace') }}"
           {{ request()->is('marketplace') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="font-medium">{{ __('admin.marketplace') }}</span>
        </a>

        <a href="/sales"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('sales*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.sales') }}"
           {{ request()->is('sales*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span class="font-medium">{{ __('admin.sales') }}</span>
        </a>

        <a href="/counterparties"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('counterparties*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.counterparties') }}"
           {{ request()->is('counterparties*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="font-medium">{{ __('admin.counterparties') }}</span>
        </a>

        <a href="/inventory"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('inventory*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.inventory') }}"
           {{ request()->is('inventory*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="font-medium">{{ __('admin.inventory') }}</span>
        </a>


        <a href="/marketplace/sync-logs"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('marketplace/sync-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.sync_logs') }}"
           {{ request()->is('marketplace/sync-logs') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-medium">{{ __('admin.sync_logs') }}</span>
        </a>

        {{-- AI Агенты - временно скрыт до завершения разработки --}}

        <a href="/tasks"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('tasks*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.tasks') }}"
           {{ request()->is('tasks*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="font-medium">{{ __('admin.tasks') }}</span>
        </a>

        <a href="/replenishment"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('replenishment*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.planning') }}"
           {{ request()->is('replenishment*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span class="font-medium">{{ __('admin.planning') }}</span>
        </a>

        <a href="/finance"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('finance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.finance') }}"
           {{ request()->is('finance*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 0V3m0 5c-2.761 0-5 1.79-5 4v1h10v-1c0-2.21-2.239-4-5-4zm-5 6h10v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="font-medium">{{ __('admin.finance') }}</span>
        </a>

        <a href="/ap"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('ap*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.accounts_payable') }}"
           {{ request()->is('ap*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-medium">{{ __('admin.accounts_payable') }}</span>
        </a>

        <a href="/pricing"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('pricing*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.pricing') }}"
           {{ request()->is('pricing*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M3 10h18M3 16h18"/>
            </svg>
            <span class="font-medium">{{ __('admin.pricing') }}</span>
        </a>

        <hr class="my-2 border-gray-200">

        <a href="/company/profile"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('company/*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('admin.company_profile') }}"
           {{ request()->is('company/*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="font-medium">{{ __('admin.company_profile') }}</span>
        </a>

        <a href="/settings"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('settings*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
           aria-label="{{ __('app.settings.title') }}"
           {{ request()->is('settings*') ? 'aria-current="page"' : '' }}>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="font-medium">{{ __('app.settings.title') }}</span>
        </a>

        <!-- Slot for page-specific content (e.g., chat history, filters) -->
        @if(isset($slot) && $slot instanceof \Illuminate\Support\HtmlString && !empty(trim($slot)))
            <div class="border-t border-gray-200 pt-4 mt-4">
                {{ $slot }}
            </div>
        @endif
    </nav>

    <!-- Language Switcher - Fixed at bottom -->
    <div class="px-4 pb-3 flex-shrink-0 border-t border-gray-200 pt-3">
        @include('components.dashboard-language-switcher')
    </div>

    <!-- User Menu - Fixed at bottom -->
    <div class="p-4 border-t border-gray-200 flex-shrink-0" role="region" aria-label="{{ __('admin.user_menu') }}">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-medium"
                 x-text="($store.auth.user?.name || $store.auth.user?.email || '?')[0].toUpperCase()">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate" x-text="$store.auth.user?.name || $store.auth.user?.email"></p>
            </div>
            <button @click="$store.auth.logout(); window.location.href='/login'"
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
