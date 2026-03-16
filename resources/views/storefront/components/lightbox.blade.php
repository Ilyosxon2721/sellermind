{{-- Lightbox — полноэкранный просмотр фото с зумом и свайпом --}}
<div
    x-data="lightboxViewer()"
    x-on:open-lightbox.window="open($event.detail)"
    x-cloak
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] bg-black/95 flex flex-col"
    @keydown.escape.window="close()"
    @keydown.left.window="prev()"
    @keydown.right.window="next()"
>
    {{-- Верхняя панель --}}
    <div class="flex items-center justify-between px-4 py-3 text-white shrink-0">
        <span class="text-sm font-medium" x-text="(activeIndex + 1) + ' / ' + images.length"></span>
        <button @click="close()" class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Основное изображение --}}
    <div
        class="flex-1 flex items-center justify-center relative overflow-hidden select-none"
        @touchstart="onTouchStart($event)"
        @touchmove="onTouchMove($event)"
        @touchend="onTouchEnd($event)"
        @dblclick="toggleZoom($event)"
    >
        {{-- Стрелка влево --}}
        <button
            x-show="images.length > 1"
            @click="prev()"
            class="absolute left-2 sm:left-4 z-10 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Изображение --}}
        <div
            class="w-full h-full flex items-center justify-center px-16"
            :style="`transform: scale(${zoom}) translate(${panX}px, ${panY}px); transition: ${zooming ? 'none' : 'transform 0.3s ease'};`"
        >
            <img
                :src="images[activeIndex]?.url"
                :alt="images[activeIndex]?.alt || ''"
                class="max-w-full max-h-full object-contain"
                @load="imageLoaded = true"
                draggable="false"
            >
        </div>

        {{-- Стрелка вправо --}}
        <button
            x-show="images.length > 1"
            @click="next()"
            class="absolute right-2 sm:right-4 z-10 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Миниатюры --}}
    <div x-show="images.length > 1" class="shrink-0 px-4 py-3 overflow-x-auto">
        <div class="flex gap-2 justify-center">
            <template x-for="(img, idx) in images" :key="idx">
                <button
                    @click="activeIndex = idx; resetZoom()"
                    class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 transition-all"
                    :class="activeIndex === idx ? 'border-white ring-2 ring-white/30' : 'border-white/20 hover:border-white/50 opacity-60 hover:opacity-100'"
                >
                    <img :src="img.url" :alt="img.alt || ''" class="w-full h-full object-cover">
                </button>
            </template>
        </div>
    </div>
</div>

<script>
    function lightboxViewer() {
        return {
            visible: false,
            images: [],
            activeIndex: 0,
            zoom: 1,
            panX: 0,
            panY: 0,
            zooming: false,
            imageLoaded: false,

            // Touch
            touchStartX: 0,
            touchStartY: 0,
            touchStartDist: 0,
            initialZoom: 1,
            swiping: false,

            open(detail) {
                this.images = detail.images || [];
                this.activeIndex = detail.startIndex || 0;
                this.resetZoom();
                this.visible = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.visible = false;
                document.body.style.overflow = '';
            },

            prev() {
                this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length;
                this.resetZoom();
            },

            next() {
                this.activeIndex = (this.activeIndex + 1) % this.images.length;
                this.resetZoom();
            },

            toggleZoom(e) {
                if (this.zoom > 1) {
                    this.resetZoom();
                } else {
                    this.zoom = 2.5;
                }
            },

            resetZoom() {
                this.zooming = false;
                this.zoom = 1;
                this.panX = 0;
                this.panY = 0;
            },

            getTouchDist(touches) {
                if (touches.length < 2) return 0;
                const dx = touches[0].clientX - touches[1].clientX;
                const dy = touches[0].clientY - touches[1].clientY;
                return Math.sqrt(dx * dx + dy * dy);
            },

            onTouchStart(e) {
                if (e.touches.length === 2) {
                    this.touchStartDist = this.getTouchDist(e.touches);
                    this.initialZoom = this.zoom;
                    this.zooming = true;
                } else if (e.touches.length === 1) {
                    this.touchStartX = e.touches[0].clientX;
                    this.touchStartY = e.touches[0].clientY;
                    this.swiping = true;
                }
            },

            onTouchMove(e) {
                if (e.touches.length === 2 && this.touchStartDist > 0) {
                    e.preventDefault();
                    const dist = this.getTouchDist(e.touches);
                    const scale = dist / this.touchStartDist;
                    this.zoom = Math.max(1, Math.min(5, this.initialZoom * scale));
                } else if (e.touches.length === 1 && this.zoom > 1) {
                    e.preventDefault();
                    this.panX += e.touches[0].clientX - this.touchStartX;
                    this.panY += e.touches[0].clientY - this.touchStartY;
                    this.touchStartX = e.touches[0].clientX;
                    this.touchStartY = e.touches[0].clientY;
                }
            },

            onTouchEnd(e) {
                if (this.zooming) {
                    this.zooming = false;
                    if (this.zoom <= 1.1) this.resetZoom();
                    return;
                }

                if (this.swiping && this.zoom <= 1 && e.changedTouches.length) {
                    const dx = e.changedTouches[0].clientX - this.touchStartX;
                    if (Math.abs(dx) > 60) {
                        dx > 0 ? this.prev() : this.next();
                    }
                }
                this.swiping = false;
            }
        }
    }
</script>
