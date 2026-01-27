<!-- Hamburger Menu Button -->
<button @click="sidebarOpen = !sidebarOpen"
        class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-white shadow-lg border border-gray-200 hover:bg-gray-50 transition-all"
        aria-label="<?php echo e(__('admin.open_navigation')); ?>"
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

<!--  Sidebar Overlay (Mobile) -->
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
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/components/hamburger-menu.blade.php ENDPATH**/ ?>