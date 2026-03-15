<!-- Mobile Top Navbar -->
<div class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between h-14 px-4">
        <!-- Hamburger Button -->
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2 -ml-2 rounded-lg hover:bg-gray-100 transition-colors"
                aria-label="{{ __('admin.open_navigation') }}"
                :aria-expanded="sidebarOpen.toString()">
            <svg class="w-6 h-6 text-gray-700 transition-transform duration-200"
                 :class="sidebarOpen ? 'rotate-90' : ''"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24"
                 aria-hidden="true">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      :d="sidebarOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/>
            </svg>
        </button>

        <!-- Logo / App Name -->
        <div class="flex items-center space-x-2">
            <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 100 100">
                    <path d="M50 15L15 35v30l35 20 35-20V35L50 15zm0 10l25 14v22L50 75 25 61V39l25-14z"/>
                </svg>
            </div>
            <span class="text-base font-semibold text-gray-900">SellerMind</span>
        </div>

        <!-- Spacer for symmetry -->
        <div class="w-10"></div>
    </div>
</div>

<!-- Sidebar Overlay (Mobile) -->
<div x-show="sidebarOpen"
     x-cloak
     @click="sidebarOpen = false"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="lg:hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-30"
     aria-hidden="true"></div>
