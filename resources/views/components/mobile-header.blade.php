<!-- Mobile Header with Hamburger Menu (visible only on mobile in browser mode, hidden in PWA mode) -->
<div x-data="{ isPWA: window.isPWAInstalled || false }"
     x-show="!isPWA"
     class="lg:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
    <!-- Hamburger Menu Button -->
    <button @click="sidebarOpen = !sidebarOpen"
            type="button"
            class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Logo -->
    <div class="flex items-center space-x-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-blue-700 text-white flex items-center justify-center shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h1 class="text-base font-bold text-gray-900">SellerMind</h1>
    </div>

    <!-- Right side placeholder (for future actions) -->
    <div class="w-10"></div>
</div>

<!-- Spacer to push content below fixed header on mobile -->
<div class="lg:hidden h-14"></div>
