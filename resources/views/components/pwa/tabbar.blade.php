@props(['active' => 'home'])

<nav class="pwa-only sm-tabbar">
    <a href="/home" class="sm-tab {{ $active === 'home' ? 'active' : '' }}">
        <span class="sm-tab-icon">&#127968;</span>
        <span class="sm-tab-label">{{ __('admin.home') }}</span>
    </a>
    <a href="/warehouse" class="sm-tab {{ $active === 'warehouse' ? 'active' : '' }}">
        <span class="sm-tab-icon">&#128230;</span>
        <span class="sm-tab-label">{{ __('admin.warehouse_documents') }}</span>
    </a>
    <a href="/marketplace" class="sm-tab {{ $active === 'marketplace' ? 'active' : '' }}">
        <span class="sm-tab-icon">&#9889;</span>
        <span class="sm-tab-label">{{ __('admin.marketplace') }}</span>
        @if(isset($newOrders) && $newOrders > 0)
            <span class="sm-tab-badge">{{ $newOrders }}</span>
        @endif
    </a>
    <a href="/sales" class="sm-tab {{ $active === 'sales' ? 'active' : '' }}">
        <span class="sm-tab-icon">&#128722;</span>
        <span class="sm-tab-label">{{ __('admin.sales') }}</span>
    </a>
    <a href="#" @click.prevent="$dispatch('open-more-menu')" class="sm-tab {{ $active === 'more' ? 'active' : '' }}">
        <span class="sm-tab-icon">&bull;&bull;&bull;</span>
        <span class="sm-tab-label">{{ __('app.settings.navigation.more') }}</span>
    </a>
</nav>
