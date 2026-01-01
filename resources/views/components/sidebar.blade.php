<!-- Sidebar Component -->
<div class="w-72 bg-white border-r border-gray-200 flex flex-col h-full">
    <!-- Logo -->
    <div class="p-4 border-b border-gray-200 flex-shrink-0">
        <a href="/home" class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <h1 class="font-bold text-gray-900">SellerMind</h1>
                <p class="text-xs text-gray-500" x-text="$store.auth.currentCompany?.name || 'Выберите компанию'"></p>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-1 flex-shrink-0">
        <a href="/home"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('home') || request()->is('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-medium">Главная</span>
        </a>
        
        <div class="flex items-center justify-between space-x-3 px-3 py-2.5 rounded-lg text-gray-400 cursor-not-allowed">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="font-medium">Чат</span>
            </div>
            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">Скоро</span>
        </div>

        <div class="px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Склад</div>
        <div x-data="{open: {{ request()->is('warehouse*') ? 'true' : 'false' }}}">
            <button type="button"
                    class="flex items-center justify-between w-full px-3 py-2 rounded-lg transition text-gray-700 hover:bg-gray-100"
                    @click="open = !open">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    <span class="font-medium">Остатки/док-ты</span>
                </div>
                <svg class="w-4 h-4 text-gray-500 transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div class="ml-6 space-y-1" x-show="open" x-cloak>
                <a href="/warehouse"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Дашборд</span>
                </a>
                <a href="/warehouse/balance"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/balance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Остатки</span>
                </a>
                <a href="/warehouse/in"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/in*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Оприходование</span>
                </a>
                <a href="/warehouse/list"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/list*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Склады</span>
                </a>
                <a href="/warehouse/documents"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/documents*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Документы</span>
                </a>
                <a href="/warehouse/reservations"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/reservations*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Резервы</span>
                </a>
                <a href="/warehouse/ledger"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('warehouse/ledger*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Журнал движений</span>
                </a>
                <a href="/products"
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg transition {{ request()->is('products*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span class="text-xs">•</span>
                    <span class="text-sm">Товары</span>
                </a>
            </div>
        </div>

        <a href="/marketplace"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('marketplace') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="font-medium">Маркетплейсы</span>
        </a>

        <a href="/sales"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('sales*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span class="font-medium">Продажи</span>
        </a>

        <a href="/counterparties"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('counterparties*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="font-medium">Контрагенты</span>
        </a>

        <a href="/inventory"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('inventory*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="font-medium">Инвентаризация</span>
        </a>


        <a href="/marketplace/sync-logs"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('marketplace/sync-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-medium">Журнал логов</span>
        </a>

        <div class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-400 cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="font-medium">AI Агенты (в разработке)</span>
        </div>

        <a href="/tasks"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('tasks*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="font-medium">Задачи</span>
        </a>

        <a href="/replenishment"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('replenishment*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span class="font-medium">Планирование</span>
        </a>

        <a href="/ap"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('ap*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3zm0 0v5m0 0a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
            <span class="font-medium">Финансы (AP)</span>
        </a>

        <a href="/pricing"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('pricing*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M3 10h18M3 16h18"/>
            </svg>
            <span class="font-medium">Цены</span>
        </a>

        <hr class="my-2 border-gray-200">

        <a href="/company/profile"
           class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition {{ request()->is('company/*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="font-medium">Профиль компании</span>
        </a>
    </nav>

    <!-- Slot for page-specific content (e.g., chat history, filters) -->
    @if(isset($slot) && $slot instanceof \Illuminate\Support\HtmlString && !empty(trim($slot)))
        <div class="flex-1 overflow-y-auto border-t border-gray-200">
            {{ $slot }}
        </div>
    @else
        <!-- Default: Quick Actions -->
        <div class="flex-1 overflow-y-auto px-4 pt-4 border-t border-gray-200">
            <p class="px-3 text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Быстрые действия</p>
            <div class="space-y-1">
                <a href="/chat?mode=cards" class="w-full flex items-center space-x-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <span>📦</span>
                    <span>Создать карточку</span>
                </a>
                <a href="/chat?mode=reviews" class="w-full flex items-center space-x-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <span>💬</span>
                    <span>Ответ на отзыв</span>
                </a>
                <a href="/chat?mode=photos" class="w-full flex items-center space-x-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <span>🖼️</span>
                    <span>Сгенерировать фото</span>
                </a>
            </div>
        </div>
    @endif

    <!-- User Menu -->
    <div class="p-4 border-t border-gray-200 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-medium"
                 x-text="($store.auth.user?.name || $store.auth.user?.email || '?')[0].toUpperCase()">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate" x-text="$store.auth.user?.name || $store.auth.user?.email"></p>
            </div>
            <button @click="$store.auth.logout(); window.location.href='/login'"
                    class="text-gray-400 hover:text-gray-600"
                    title="Выйти">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </div>
    </div>
</div>
