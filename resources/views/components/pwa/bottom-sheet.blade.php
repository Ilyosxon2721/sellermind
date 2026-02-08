@props(['id' => 'bottomSheet'])

<div x-data="{ visible: false }"
     x-show="visible"
     x-cloak
     @open-{{ $id }}.window="visible = true"
     @close-{{ $id }}.window="visible = false"
     class="pwa-only sm-bottom-sheet"
     :class="{ 'visible': visible }">

    {{-- Backdrop --}}
    <div class="sm-bottom-sheet-backdrop" @click="visible = false; $dispatch('close-{{ $id }}')"></div>

    {{-- Content --}}
    <div class="sm-bottom-sheet-content">
        <div class="sm-bottom-sheet-handle"></div>
        {{ $slot }}
    </div>
</div>
