@extends('layouts.app')

@section('content')
<style>[x-cloak]{display:none!important;}</style>
@php
    $attrListMapped = $attributesList->map(function($a) {
        return $a->only(['id','name','type','allowed_values','unit','is_variant_level']);
    })->values();
    
    $sizesData = ($globalSizes ?? collect())->map(fn($s) => ['id' => $s->id, 'value' => $s->value, 'code' => $s->code])->values();
    $colorsData = ($globalColors ?? collect())->map(fn($c) => ['id' => $c->id, 'value' => $c->value, 'code' => $c->code, 'hex' => $c->color_hex])->values();
@endphp
<script>
    window.__productEditorData = {
        initialState: @json($initialState),
        attributesList: @json($attrListMapped),
        globalSizes: @json($sizesData),
        globalColors: @json($colorsData),
        hasId: {{ $product->id ? 'true' : 'false' }},
        publishUrl: '{{ $product->id ? route("web.products.publish", $product) : "" }}'
    };

    function productEditor() {
        const data = window.__productEditorData || {};
        const initial = data.initialState || {};
        const attributeDefs = data.attributesList || [];
        const publishUrl = data.publishUrl || '';
        const globalSizes = data.globalSizes || [];
        const globalColors = data.globalColors || [];

        return {
            step: 1,
            currentStep: 1,
            steps: ['Основная информация', 'Размеры и цвета', 'Варианты', 'Характеристики', 'Медиа', 'Цены и остатки'],
            product: initial.product || {},
            options: initial.options || [],
            variants: initial.variants || [],
            images: initial.images || [],
            attributes: initial.attributes || { product: [], variants: [] },
            channelSettings: initial.channel_settings || [],
            channelVariants: initial.channel_variants || [],
            channels: [{code: 'wb', name: 'Wildberries'}, {code: 'ozon', name: 'Ozon'}, {code: 'ym', name: 'Yandex Market'}, {code: 'uzum', name: 'Uzum'}],
            attributeDefs: attributeDefs,

            // Global options
            globalSizes: globalSizes,
            globalColors: globalColors,
            selectedSizes: [],
            selectedColors: [],

            // Custom sizes and colors (user-added)
            customSizes: [],
            customColors: [],
            newSizeValue: '',
            newColorValue: '',
            newColorHex: '#6366f1',

            // Initialize selected sizes/colors from existing variants on load
            init() {
                this.initSelectedFromVariants();
            },

            // Initialize selected sizes/colors from existing variants
            initSelectedFromVariants() {
                if (!this.variants || this.variants.length === 0) return;

                const sizeCodes = new Set();
                const colorCodes = new Set();

                // Build lookup maps for global options by value (case-insensitive)
                const sizeByValue = {};
                const colorByValue = {};
                this.globalSizes.forEach(s => { sizeByValue[s.value.toLowerCase()] = s.code; });
                this.globalColors.forEach(c => { colorByValue[c.value.toLowerCase()] = c.code; });

                this.variants.forEach(variant => {
                    // Try to parse from option_values_summary: "Размер: M, Цвет: Белый"
                    const summary = variant.option_values_summary || '';

                    // Extract size value
                    const sizeMatch = summary.match(/Размер:\s*([^,]+)/i);
                    if (sizeMatch) {
                        const sizeValue = sizeMatch[1].trim().toLowerCase();
                        if (sizeByValue[sizeValue]) {
                            sizeCodes.add(sizeByValue[sizeValue]);
                        }
                    }

                    // Extract color value
                    const colorMatch = summary.match(/Цвет:\s*(.+)$/i);
                    if (colorMatch) {
                        const colorValue = colorMatch[1].trim().toLowerCase();
                        if (colorByValue[colorValue]) {
                            colorCodes.add(colorByValue[colorValue]);
                        }
                    }

                    // Also use size_code/color_code if stored on variant
                    if (variant.size_code) sizeCodes.add(variant.size_code);
                    if (variant.color_code) colorCodes.add(variant.color_code);
                });

                this.selectedSizes = Array.from(sizeCodes);
                this.selectedColors = Array.from(colorCodes);
            },

            // Add custom size (saves to database for future use)
            async addCustomSize() {
                const value = this.newSizeValue.trim();
                if (!value) return;
                const code = value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                if (this.globalSizes.some(s => s.code === code) || this.customSizes.some(s => s.code === code)) {
                    alert('Такой размер уже существует');
                    return;
                }

                try {
                    const response = await fetch('/api/option-values', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            type: 'size',
                            value: value,
                            code: code
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        // Add to globalSizes so it's available for future use
                        this.globalSizes.push({ id: result.data.id, value: result.data.value, code: result.data.code });
                        this.selectedSizes.push(code);
                        this.newSizeValue = '';
                    } else {
                        alert(result.error || 'Ошибка при сохранении размера');
                    }
                } catch (error) {
                    console.error('Error saving custom size:', error);
                    // Fallback: add locally without saving
                    this.customSizes.push({ id: 'custom-' + Date.now(), value: value, code: code });
                    this.selectedSizes.push(code);
                    this.newSizeValue = '';
                }
            },

            // Remove custom size
            removeCustomSize(idx) {
                const size = this.customSizes[idx];
                this.selectedSizes = this.selectedSizes.filter(s => s !== size.code);
                this.customSizes.splice(idx, 1);
            },

            // Add custom color (saves to database for future use)
            async addCustomColor() {
                const value = this.newColorValue.trim();
                if (!value) return;
                const code = value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                if (this.globalColors.some(c => c.code === code) || this.customColors.some(c => c.code === code)) {
                    alert('Такой цвет уже существует');
                    return;
                }

                try {
                    const response = await fetch('/api/option-values', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            type: 'color',
                            value: value,
                            code: code,
                            color_hex: this.newColorHex
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        // Add to globalColors so it's available for future use
                        this.globalColors.push({ id: result.data.id, value: result.data.value, code: result.data.code, hex: result.data.color_hex });
                        this.selectedColors.push(code);
                        this.newColorValue = '';
                    } else {
                        alert(result.error || 'Ошибка при сохранении цвета');
                    }
                } catch (error) {
                    console.error('Error saving custom color:', error);
                    // Fallback: add locally without saving
                    this.customColors.push({ id: 'custom-' + Date.now(), value: value, code: code, hex: this.newColorHex });
                    this.selectedColors.push(code);
                    this.newColorValue = '';
                }
            },

            // Remove custom color
            removeCustomColor(idx) {
                const color = this.customColors[idx];
                this.selectedColors = this.selectedColors.filter(c => c !== color.code);
                this.customColors.splice(idx, 1);
            },

            nextStep() { if (this.step < this.steps.length) this.step++; },
            prevStep() { if (this.step > 1) this.step--; },
            addOption() { this.options.push({name: '', code: '', type: 'select', is_variant_dimension: true, values: []}); },
            addOptionValue(idx) { this.options[idx].values.push({value: '', code: '', color_hex: null, sort_order: (this.options[idx].values.length || 0)}); },
            removeOptionValue(oIdx, vIdx) { this.options[oIdx].values.splice(vIdx, 1); },
            addVariant() { this.variants.push({sku: '', barcode: '', option_values_summary: '', is_active: true}); },
            removeVariant(idx) { this.variants.splice(idx, 1); },

            // Generate variants from selected sizes and colors
            generateVariantsFromSelection() {
                const article = this.product.article || 'SKU';
                const sizes = this.selectedSizes;
                const colors = this.selectedColors;

                if (!sizes.length || !colors.length) {
                    alert('Выберите хотя бы один размер и один цвет');
                    return;
                }

                // Combine global and custom options for lookups
                const allSizes = [...this.globalSizes, ...this.customSizes];
                const allColors = [...this.globalColors, ...this.customColors];

                this.variants = [];

                for (const sizeCode of sizes) {
                    const sizeObj = allSizes.find(s => s.code === sizeCode);
                    const sizeValue = sizeObj ? sizeObj.value : sizeCode;

                    for (const colorCode of colors) {
                        const colorObj = allColors.find(c => c.code === colorCode);
                        const colorValue = colorObj ? colorObj.value : colorCode;

                        // SKU format: Article-SizeCode-ColorCode
                        const sku = `${article}-${sizeCode.toUpperCase()}-${colorCode.toUpperCase()}`;

                        this.variants.push({
                            sku: sku,
                            option_values_summary: `Размер: ${sizeValue}, Цвет: ${colorValue}`,
                            barcode: '',
                            weight_g: null,
                            length_mm: null,
                            width_mm: null,
                            height_mm: null,
                            is_active: true,
                            size_code: sizeCode,
                            color_code: colorCode
                        });
                    }
                }

                // Auto-advance to step 3 to show variants table
                this.step = 3;
            },

            // Legacy generate from options (keeping for backwards compatibility)
            generateVariants() {
                if (!this.options.length) return;
                const variantOptions = this.options.filter(o => o.is_variant_dimension && o.values && o.values.length);
                if (!variantOptions.length) return;
                const combos = variantOptions.reduce((acc, option) => {
                    const vals = option.values;
                    if (!acc.length) return vals.map(v => ({ids: [v.id], summary: `${option.name}: ${v.value}`}));
                    const next = [];
                    acc.forEach(c => { vals.forEach(v => { next.push({ids: [...c.ids, v.id], summary: `${c.summary}, ${option.name}: ${v.value}`}); }); });
                    return next;
                }, []);
                this.variants = combos.map((c, idx) => ({option_values_summary: c.summary, sku: `VAR-${idx + 1}`, is_active: true, option_value_ids: c.ids.filter(Boolean)}));
            },
            addProductAttribute() { this.attributes.product.push({attribute_id: null, value_string: ''}); },
            addVariantAttribute() { this.attributes.variants.push({product_variant_id: null, attribute_id: null, value_string: ''}); },

            // Image management
            maxImages: 10,
            draggingIdx: null,

            addImage() {
                if (this.images.length >= this.maxImages) {
                    alert('Максимум ' + this.maxImages + ' изображений');
                    return;
                }
                this.images.push({file_path: '', alt_text: '', is_main: this.images.length === 0, sort_order: this.images.length, variant_id: '', uploading: false});
            },

            async addImageFromFile(event) {
                const file = event.target.files[0];
                if (!file) return;
                if (this.images.length >= this.maxImages) {
                    alert('Максимум ' + this.maxImages + ' изображений');
                    return;
                }
                // Add new image slot
                const idx = this.images.length;
                this.images.push({file_path: '', alt_text: '', is_main: idx === 0, sort_order: idx, variant_id: '', uploading: true});
                // Upload
                await this.uploadImageAtIndex(file, idx);
                // Clear input
                event.target.value = '';
            },

            removeImage(idx) {
                this.images.splice(idx, 1);
                // Update sort orders
                this.images.forEach((img, i) => img.sort_order = i);
            },

            setMainImage(idx) {
                this.images.forEach((img, i) => img.is_main = (i === idx));
            },

            moveImage(idx, dir) {
                const target = idx + dir;
                if (target < 0 || target >= this.images.length) return;
                const tmp = this.images[target];
                this.images[target] = this.images[idx];
                this.images[idx] = tmp;
                // Update sort orders
                this.images.forEach((img, i) => img.sort_order = i);
            },

            // Drag & Drop
            dragStart(event, idx) {
                this.draggingIdx = idx;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', idx);
            },

            dragOver(event, idx) {
                if (this.draggingIdx === null || this.draggingIdx === idx) return;
                // Reorder during drag
                const draggedImage = this.images[this.draggingIdx];
                this.images.splice(this.draggingIdx, 1);
                this.images.splice(idx, 0, draggedImage);
                this.draggingIdx = idx;
            },

            drop(event, idx) {
                event.preventDefault();
                // Update sort orders after drop
                this.images.forEach((img, i) => img.sort_order = i);
                this.draggingIdx = null;
            },

            dragEnd(event) {
                this.draggingIdx = null;
            },

            // Image URL helper
            getImageUrl(path) {
                if (!path) return '';
                // If already absolute URL
                if (path.startsWith('http://') || path.startsWith('https://')) {
                    return path;
                }
                // If storage path (starts with /storage)
                if (path.startsWith('/storage')) {
                    return path;
                }
                // If relative path, prepend /storage
                if (path.startsWith('products/')) {
                    return '/storage/' + path;
                }
                return path;
            },

            handleImageError(event, image) {
                console.error('Image load error:', image.file_path);
                // Hide broken image
                event.target.style.display = 'none';
            },

            async uploadImageAtIndex(file, idx) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Выберите файл изображения (jpg, png, gif, webp)');
                    if (!this.images[idx].file_path) this.images.splice(idx, 1);
                    return;
                }

                // Validate file size (max 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('Максимальный размер файла 10 МБ');
                    if (!this.images[idx].file_path) this.images.splice(idx, 1);
                    return;
                }

                const originalPath = this.images[idx].file_path;
                this.images[idx].uploading = true;

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('/api/products/upload-image', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });

                    if (response.status === 401) {
                        throw new Error('Сессия истекла. Перезагрузите страницу.');
                    }
                    if (response.status === 403) {
                        throw new Error('Нет доступа.');
                    }

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Ошибка сервера. Перезагрузите страницу.');
                    }

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Ошибка загрузки');
                    }

                    this.images[idx].file_path = data.path || data.url;
                    this.images[idx].alt_text = this.images[idx].alt_text || file.name.replace(/\.[^/.]+$/, '');
                    this.images[idx].uploading = false;
                } catch (e) {
                    console.error('Upload error:', e);
                    alert(e.message || 'Ошибка загрузки файла');
                    this.images[idx].uploading = false;
                    // Restore original or remove if it was new
                    if (originalPath) {
                        this.images[idx].file_path = originalPath;
                    } else {
                        this.images.splice(idx, 1);
                    }
                }
            },
            async uploadImage(event, idx) {
                const file = event.target.files[0];
                if (!file) return;
                event.target.value = ''; // Clear input
                await this.uploadImageAtIndex(file, idx);
            },
            settingFor(code) { let s = this.channelSettings.find(c => c.channel_code === code || c.channel_id === code); if (!s) { s = {channel_code: code, is_enabled: false, status: 'draft'}; this.channelSettings.push(s); } return s; },
            channelEnabled(code) { const s = this.channelSettings.find(c => c.channel_code === code || c.channel_id === code); return s ? !!s.is_enabled : false; },
            toggleChannel(code, value) { const s = this.settingFor(code); s.is_enabled = value; s.status = s.status || 'draft'; },
            ensureChannelVariant(channelCode, variant) { let row = this.channelVariants.find(cv => (cv.channel_code === channelCode || cv.channel_id === channelCode) && ((variant.id && cv.product_variant_id === variant.id) || (!variant.id && cv.variant_sku === variant.sku))); if (!row) { row = {channel_code: channelCode, product_variant_id: variant.id, variant_sku: variant.sku, status: 'draft'}; this.channelVariants.push(row); } return row; },
            channelVariantValue(channelCode, variant, field) { const row = this.ensureChannelVariant(channelCode, variant); if (row[field] === undefined) row[field] = field === 'status' ? 'draft' : null; return {get value() { return row[field]; }, set value(v) { row[field] = v; }}; },
            copyBasePrices(channelCode) { this.variants.forEach(variant => { const row = this.ensureChannelVariant(channelCode, variant); row.price = variant.price_default; row.old_price = variant.old_price_default; }); },
            submit(alsoPublish = false) {
                // Serialize hidden inputs
                this.$refs.optionsInput.value = JSON.stringify(this.options);
                this.$refs.variantsInput.value = JSON.stringify(this.variants);
                this.$refs.imagesInput.value = JSON.stringify(this.images);
                this.$refs.attributesProductInput.value = JSON.stringify(this.attributes.product);
                this.$refs.attributesVariantsInput.value = JSON.stringify(this.attributes.variants);
                this.$refs.channelSettingsInput.value = JSON.stringify(this.channelSettings);
                this.$refs.channelVariantsInput.value = JSON.stringify(this.channelVariants);

                // Refresh CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const csrfInput = this.$refs.form.querySelector('input[name="_token"]');
                if (csrfInput && csrfToken) {
                    csrfInput.value = csrfToken;
                }

                // Publish if requested
                if (alsoPublish && publishUrl) {
                    const fd = new FormData();
                    fd.append('_token', csrfToken);
                    this.channels.forEach(c => fd.append('channels[]', c.code));
                    navigator.sendBeacon(publishUrl, fd);
                }

                // Submit form
                this.$refs.form.submit();
            },
            publish() { this.submit(true); }
        }
    }
</script>

<!-- Toast Notifications (outside x-cloak for immediate visibility) -->
@if(session('success') || session('error') || $errors->any())
<div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)" 
     class="fixed top-4 right-4 z-[9999] max-w-md">
    @if(session('success'))
    <div class="bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-green-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-red-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span class="font-medium">{{ session('error') }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-2xl border border-red-400">
        <div class="flex items-center space-x-3 mb-2">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-semibold">Ошибки валидации:</span>
            <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
        </div>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endif

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="productEditor()" x-cloak
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">{{ $product->id ? 'Редактирование товара' : 'Создание товара' }}</h1>
                    <p class="text-sm text-gray-500">Заполните карточку товара по шагам</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($product->id)
                        <form method="POST" action="{{ route('web.products.destroy', $product) }}" onsubmit="return confirm('Архивировать товар?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors">В архив</button>
                        </form>
                    @endif
                    <a href="{{ route('web.products.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors">Назад к списку</a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6">
            <form x-ref="form"
                  method="POST"
                  action="{{ $product->id ? route('web.products.update', $product) : route('web.products.store') }}"
                  class="space-y-6"
                  @submit.prevent="submit">
                @csrf
                @if($product->id) @method('PUT') @endif

                <input type="hidden" name="options" x-ref="optionsInput">
                <input type="hidden" name="variants" x-ref="variantsInput">
                <input type="hidden" name="images" x-ref="imagesInput">
                <input type="hidden" name="attributes_product" x-ref="attributesProductInput">
                <input type="hidden" name="attributes_variants" x-ref="attributesVariantsInput">
                <input type="hidden" name="channel_settings" x-ref="channelSettingsInput">
                <input type="hidden" name="channel_variants" x-ref="channelVariantsInput">

                <!-- Steps Navigation -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 overflow-x-auto">
                        <template x-for="(label, idx) in steps" :key="idx">
                            <button type="button"
                                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap"
                                    :class="step === (idx+1) ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-lg shadow-indigo-500/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    @click="step = idx+1"
                                    x-text="(idx+1) + '. ' + label"></button>
                        </template>
                    </div>
                </div>

                <!-- Step 1: Basic Info -->
                <section x-show="step === 1" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Основная информация</h2>
                        <p class="text-sm text-gray-500 mt-1">Заполните базовые данные о товаре: название, артикул, бренд и категорию</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Название*</label>
                            <input type="text" name="product[name]" x-model="product.name" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Артикул*</label>
                            <input type="text" name="product[article]" x-model="product.article" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Бренд</label>
                            <input type="text" name="product[brand_name]" x-model="product.brand_name" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div x-data="{ showNewCategory: false, newCategoryName: '' }">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                            <div class="flex gap-2">
                                <template x-if="!showNewCategory">
                                    <select name="product[category_id]" x-model="product.category_id" class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                        <option value="">Не выбрано</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </template>
                                <template x-if="showNewCategory">
                                    <input type="text"
                                           x-model="newCategoryName"
                                           name="product[new_category_name]"
                                           class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="Введите название категории">
                                </template>
                                <button type="button"
                                        @click="showNewCategory = !showNewCategory; if(!showNewCategory) newCategoryName = ''"
                                        class="px-3 py-2 text-sm rounded-xl transition-colors"
                                        :class="showNewCategory ? 'bg-gray-200 text-gray-700' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'">
                                    <span x-text="showNewCategory ? 'Отмена' : '+ Новая'"></span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Страна происхождения</label>
                            <input type="text" name="product[country_of_origin]" x-model="product.country_of_origin" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Производитель</label>
                            <input type="text" name="product[manufacturer]" x-model="product.manufacturer" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Единица измерения</label>
                            <select name="product[unit]" x-model="product.unit" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Выберите единицу</option>
                                <option value="шт">шт — штука</option>
                                <option value="л">л — литр</option>
                                <option value="мл">мл — миллилитр</option>
                                <option value="кг">кг — килограмм</option>
                                <option value="г">г — грамм</option>
                                <option value="м">м — метр</option>
                                <option value="см">см — сантиметр</option>
                                <option value="м²">м² — квадратный метр</option>
                                <option value="упак">упак — упаковка</option>
                                <option value="комплект">комплект</option>
                                <option value="пара">пара</option>
                                <option value="набор">набор</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-6 pt-6">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product[is_active]" x-model="product.is_active" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Активен</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product[is_archived]" x-model="product.is_archived" class="w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-sm text-gray-700">Архив</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Уход</label>
                            <textarea name="product[care_instructions]" x-model="product.care_instructions" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Состав</label>
                            <textarea name="product[composition]" x-model="product.composition" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"></textarea>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Вес, г</label>
                            <input type="number" name="product[package_weight_g]" x-model="product.package_weight_g" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Длина, мм</label>
                            <input type="number" name="product[package_length_mm]" x-model="product.package_length_mm" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ширина, мм</label>
                            <input type="number" name="product[package_width_mm]" x-model="product.package_width_mm" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Высота, мм</label>
                            <input type="number" name="product[package_height_mm]" x-model="product.package_height_mm" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>
                </section>

                <!-- Step 2: Sizes and Colors Selection -->
                <section x-show="step === 2" class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Выбор размеров и цветов</h2>
                            <div class="mt-2 p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-sm text-indigo-700">
                                <p class="font-medium mb-1">💡 Как это работает?</p>
                                <p>Выберите размеры и цвета товара. Система автоматически создаст все комбинации вариантов.</p>
                                <p class="mt-1"><strong>Пример:</strong> 3 размера × 2 цвета = 6 вариантов</p>
                            </div>
                        </div>

                        <!-- Sizes Selection -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-gray-800">Размеры</h3>
                                <span class="text-sm text-gray-500">Выбрано: <span class="font-semibold text-indigo-600" x-text="selectedSizes.length"></span></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <!-- Predefined sizes -->
                                <template x-for="size in globalSizes" :key="size.id">
                                    <label class="cursor-pointer">
                                        <input type="checkbox"
                                               :value="size.code"
                                               x-model="selectedSizes"
                                               class="peer hidden">
                                        <span class="inline-block px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all
                                                     peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                     bg-white text-gray-700 border-gray-300 hover:border-indigo-300"
                                              x-text="size.value"></span>
                                    </label>
                                </template>
                                <!-- Custom sizes -->
                                <template x-for="(size, idx) in customSizes" :key="'custom-size-' + idx">
                                    <label class="cursor-pointer">
                                        <input type="checkbox"
                                               :value="size.code"
                                               x-model="selectedSizes"
                                               class="peer hidden">
                                        <span class="inline-flex items-center gap-1 px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all
                                                     peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                     bg-amber-50 text-amber-700 border-amber-300 hover:border-amber-400">
                                            <span x-text="size.value"></span>
                                            <button type="button" @click.prevent="removeCustomSize(idx)" class="ml-1 text-amber-500 hover:text-red-600">&times;</button>
                                        </span>
                                    </label>
                                </template>
                            </div>
                            <!-- Add custom size -->
                            <div class="flex gap-2 items-center">
                                <input type="text"
                                       x-model="newSizeValue"
                                       @keydown.enter.prevent="addCustomSize"
                                       class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500"
                                       placeholder="Новый размер">
                                <button type="button"
                                        @click="addCustomSize"
                                        class="px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg text-sm hover:bg-indigo-200 transition">
                                    + Добавить
                                </button>
                            </div>
                        </div>

                        <!-- Colors Selection -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-gray-800">Цвета</h3>
                                <span class="text-sm text-gray-500">Выбрано: <span class="font-semibold text-indigo-600" x-text="selectedColors.length"></span></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <!-- Predefined colors -->
                                <template x-for="color in globalColors" :key="color.id">
                                    <label class="cursor-pointer">
                                        <input type="checkbox"
                                               :value="color.code"
                                               x-model="selectedColors"
                                               class="peer hidden">
                                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all
                                                     peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                     bg-white text-gray-700 border-gray-300 hover:border-indigo-300">
                                            <span x-show="color.hex"
                                                  class="w-4 h-4 rounded-full border border-gray-300"
                                                  :style="'background-color: ' + color.hex"></span>
                                            <span x-text="color.value"></span>
                                        </span>
                                    </label>
                                </template>
                                <!-- Custom colors -->
                                <template x-for="(color, idx) in customColors" :key="'custom-color-' + idx">
                                    <label class="cursor-pointer">
                                        <input type="checkbox"
                                               :value="color.code"
                                               x-model="selectedColors"
                                               class="peer hidden">
                                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all
                                                     peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                     bg-amber-50 text-amber-700 border-amber-300 hover:border-amber-400">
                                            <span x-show="color.hex"
                                                  class="w-4 h-4 rounded-full border border-gray-300"
                                                  :style="'background-color: ' + color.hex"></span>
                                            <span x-text="color.value"></span>
                                            <button type="button" @click.prevent="removeCustomColor(idx)" class="ml-1 text-amber-500 hover:text-red-600">&times;</button>
                                        </span>
                                    </label>
                                </template>
                            </div>
                            <!-- Add custom color -->
                            <div class="flex gap-2 items-center">
                                <input type="text"
                                       x-model="newColorValue"
                                       @keydown.enter.prevent="addCustomColor"
                                       class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500"
                                       placeholder="Новый цвет">
                                <input type="color"
                                       x-model="newColorHex"
                                       class="w-10 h-8 border border-gray-300 rounded cursor-pointer">
                                <button type="button"
                                        @click="addCustomColor"
                                        class="px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg text-sm hover:bg-indigo-200 transition">
                                    + Добавить
                                </button>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-600">
                                <span x-show="selectedSizes.length > 0 && selectedColors.length > 0">
                                    Будет создано <span class="font-semibold text-indigo-600" x-text="selectedSizes.length * selectedColors.length"></span> вариантов
                                </span>
                                <span x-show="selectedSizes.length === 0 || selectedColors.length === 0" class="text-amber-600">
                                    Выберите хотя бы один размер и один цвет
                                </span>
                            </div>
                            <button type="button" 
                                    class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/25 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="selectedSizes.length === 0 || selectedColors.length === 0"
                                    @click="generateVariantsFromSelection">
                                🚀 Сгенерировать варианты
                            </button>
                        </div>
                    </div>

                    <!-- Preview of generated variants -->
                    <div x-show="variants.length > 0" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-gray-800">Сгенерировано вариантов: <span class="text-indigo-600" x-text="variants.length"></span></h3>
                            <button type="button" class="text-sm text-red-600 hover:text-red-700" @click="variants = []">Очистить все</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(variant, idx) in variants" :key="idx">
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 rounded-lg text-sm">
                                    <span x-text="variant.sku" class="font-mono text-xs"></span>
                                    <button type="button" class="text-gray-400 hover:text-red-600" @click="removeVariant(idx)">×</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </section>

                <!-- Step 3: Variants Table (formerly combined with Step 2) -->
                <section x-show="step === 3" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Таблица вариантов</h2>
                        <p class="text-sm text-gray-500 mt-1">Заполните штрихкоды и габариты для каждого варианта</p>
                    </div>

                    <div x-show="variants.length === 0" class="text-center py-8 text-gray-500">
                        <p>Нет вариантов. Вернитесь на шаг 2 и сгенерируйте варианты.</p>
                    </div>

                    <div x-show="variants.length > 0" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 bg-gray-50">
                                    <th class="px-3 py-2 rounded-l-lg">Вариант</th>
                                    <th class="px-3 py-2">SKU</th>
                                    <th class="px-3 py-2">Штрихкод</th>
                                    <th class="px-3 py-2">Вес (г)</th>
                                    <th class="px-3 py-2">Д×Ш×В (мм)</th>
                                    <th class="px-3 py-2 rounded-r-lg text-center">Активен</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(variant, idx) in variants" :key="idx">
                                    <tr>
                                        <td class="px-3 py-2">
                                            <span class="text-sm text-gray-700" x-text="variant.option_values_summary"></span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded" x-text="variant.sku"></span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" class="w-32 border rounded-lg px-2 py-1 text-sm" x-model="variant.barcode" placeholder="Штрихкод">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" class="w-20 border rounded-lg px-2 py-1 text-sm" x-model="variant.weight_g" placeholder="0">
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex gap-1">
                                                <input type="number" class="w-16 border rounded-lg px-1 py-1 text-sm" x-model="variant.length_mm" placeholder="Д">
                                                <input type="number" class="w-16 border rounded-lg px-1 py-1 text-sm" x-model="variant.width_mm" placeholder="Ш">
                                                <input type="number" class="w-16 border rounded-lg px-1 py-1 text-sm" x-model="variant.height_mm" placeholder="В">
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="checkbox" class="w-4 h-4 rounded text-indigo-600" x-model="variant.is_active">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Step 4: Attributes -->
                <section x-show="step === 4" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Характеристики</h2>
                        <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
                            <p class="font-medium mb-1">💡 Что такое характеристики?</p>
                            <p>Характеристики — это описательные свойства товара (материал, страна производства, вес). Они НЕ создают отдельных вариантов.</p>
                            <p class="mt-1"><strong>Отличие от вариантов:</strong> Материал "хлопок" — характеристика. Размер "M" — вариант.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between"><h3 class="font-medium text-gray-800">Характеристики товара</h3><button type="button" class="text-sm text-indigo-600" @click="addProductAttribute">+ Атрибут</button></div>
                            <template x-for="(attr, idx) in attributes.product" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select class="flex-1 border rounded-lg px-2 py-1.5 text-sm" x-model="attr.attribute_id"><option value="">Выберите</option><template x-for="def in attributeDefs" :key="def.id"><option :value="def.id" x-text="def.name"></option></template></select>
                                    <input type="text" class="flex-1 border rounded-lg px-2 py-1.5 text-sm" x-model="attr.value_string" placeholder="Значение">
                                    <button type="button" class="text-red-600" @click="attributes.product.splice(idx,1)">×</button>
                                </div>
                            </template>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between"><h3 class="font-medium text-gray-800">Характеристики вариантов</h3><button type="button" class="text-sm text-indigo-600" @click="addVariantAttribute">+ Атрибут</button></div>
                            <template x-for="(attr, idx) in attributes.variants" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select class="flex-1 border rounded-lg px-2 py-1.5 text-sm" x-model="attr.product_variant_id"><option value="">Вариант</option><template x-for="variant in variants" :key="variant.sku"><option :value="variant.id ?? variant.sku" x-text="variant.option_values_summary || variant.sku"></option></template></select>
                                    <select class="flex-1 border rounded-lg px-2 py-1.5 text-sm" x-model="attr.attribute_id"><option value="">Атрибут</option><template x-for="def in attributeDefs" :key="def.id"><option :value="def.id" x-text="def.name"></option></template></select>
                                    <input type="text" class="flex-1 border rounded-lg px-2 py-1.5 text-sm" x-model="attr.value_string" placeholder="Значение">
                                    <button type="button" class="text-red-600" @click="attributes.variants.splice(idx,1)">×</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <!-- Step 5: Media -->
                <section x-show="step === 5" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-6">
                    <div>
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Медиа</h2>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-500" x-text="images.length + ' / ' + maxImages + ' изображений'"></span>
                                <button type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/25 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        @click="addImage"
                                        :disabled="images.length >= maxImages">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Добавить
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
                            <p class="font-medium mb-1">Загрузка изображений</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Максимум <strong>10 фото</strong> на товар</li>
                                <li>Максимальный размер файла: <strong>10 МБ</strong></li>
                                <li>Форматы: JPG, PNG, GIF, WebP</li>
                                <li>Перетаскивайте для изменения порядка</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Images Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" x-ref="imagesGrid">
                        <template x-for="(image, idx) in images" :key="idx">
                            <div class="relative group bg-gray-50 rounded-xl border-2 border-gray-200 hover:border-indigo-400 transition-all overflow-hidden"
                                 draggable="true"
                                 @dragstart="dragStart($event, idx)"
                                 @dragover.prevent="dragOver($event, idx)"
                                 @drop="drop($event, idx)"
                                 @dragend="dragEnd($event)"
                                 :class="{'border-indigo-500 ring-2 ring-indigo-200': draggingIdx === idx}">

                                <!-- Image Preview -->
                                <div class="aspect-square relative">
                                    <!-- Loading State -->
                                    <template x-if="image.uploading">
                                        <div class="absolute inset-0 bg-gray-100 flex items-center justify-center">
                                            <div class="text-center">
                                                <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Image or Upload Placeholder -->
                                    <template x-if="!image.uploading">
                                        <div class="absolute inset-0">
                                            <template x-if="image.file_path">
                                                <img :src="getImageUrl(image.file_path)"
                                                     class="w-full h-full object-cover cursor-move"
                                                     x-on:error="handleImageError($event, image)"
                                                     :alt="image.alt_text || 'Изображение товара'">
                                            </template>
                                            <template x-if="!image.file_path">
                                                <label class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer bg-gray-100 hover:bg-gray-200 transition">
                                                    <input type="file" accept="image/*" class="hidden" @change="uploadImage($event, idx)">
                                                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span class="text-xs text-gray-500">Загрузить</span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Main Badge -->
                                    <template x-if="image.is_main">
                                        <div class="absolute top-2 left-2 px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded-lg shadow">
                                            Главное
                                        </div>
                                    </template>

                                    <!-- Order Number -->
                                    <div class="absolute top-2 right-2 w-6 h-6 bg-black/50 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                        <span x-text="idx + 1"></span>
                                    </div>

                                    <!-- Hover Controls -->
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <div class="flex gap-2">
                                            <button type="button"
                                                    class="p-2 bg-white rounded-lg shadow hover:bg-gray-100 transition"
                                                    @click="setMainImage(idx)"
                                                    :class="{'ring-2 ring-yellow-400': image.is_main}"
                                                    title="Сделать главным">
                                                <svg class="w-4 h-4" :class="image.is_main ? 'text-yellow-500' : 'text-gray-600'" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </button>
                                            <label class="p-2 bg-white rounded-lg shadow hover:bg-gray-100 transition cursor-pointer" title="Заменить">
                                                <input type="file" accept="image/*" class="hidden" @change="uploadImage($event, idx)">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                </svg>
                                            </label>
                                            <button type="button"
                                                    class="p-2 bg-red-500 text-white rounded-lg shadow hover:bg-red-600 transition"
                                                    @click="removeImage(idx)"
                                                    title="Удалить">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Info -->
                                <div class="p-2 space-y-1">
                                    <!-- Variant selector -->
                                    <select class="w-full border rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500" x-model="image.variant_id">
                                        <option value="">Общий</option>
                                        <template x-for="variant in variants" :key="variant.sku">
                                            <option :value="variant.id ?? variant.sku" x-text="variant.option_values_summary || variant.sku"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Add Image Button (if not maxed) -->
                        <template x-if="images.length < maxImages">
                            <label class="aspect-square flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition-all">
                                <input type="file" accept="image/*" class="hidden" @change="addImageFromFile($event)">
                                <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span class="text-sm text-gray-500">Добавить фото</span>
                            </label>
                        </template>
                    </div>

                    <!-- Empty state -->
                    <template x-if="images.length === 0">
                        <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl">
                            <svg class="mx-auto w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-4 text-lg text-gray-500">Нет изображений</p>
                            <p class="text-sm text-gray-400">Добавьте фотографии товара</p>
                            <label class="mt-4 inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl cursor-pointer hover:bg-indigo-700 transition">
                                <input type="file" accept="image/*" class="hidden" @change="addImageFromFile($event)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Загрузить первое фото
                            </label>
                        </div>
                    </template>
                </section>


                <!-- Step 6: Prices -->
                <section x-show="step === 6" class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Базовые цены</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-xs text-gray-500 bg-gray-50"><th class="px-3 py-2 rounded-l-lg">Вариант</th><th class="px-3 py-2">Цена ({{ $currency ?? 'UZS' }})</th><th class="px-3 py-2">Старая цена ({{ $currency ?? 'UZS' }})</th><th class="px-3 py-2 rounded-r-lg">Закупка ({{ $currency ?? 'UZS' }})</th></tr></thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(variant, idx) in variants" :key="idx">
                                        <tr>
                                            <td class="px-3 py-2" x-text="variant.option_values_summary || variant.sku || ('Вариант ' + (idx+1))"></td>
                                            <td class="px-3 py-2"><input type="number" step="0.01" class="w-24 border rounded-lg px-2 py-1" x-model="variant.price_default"></td>
                                            <td class="px-3 py-2"><input type="number" step="0.01" class="w-24 border rounded-lg px-2 py-1" x-model="variant.old_price_default"></td>
                                            <td class="px-3 py-2"><input type="number" step="0.01" class="w-24 border rounded-lg px-2 py-1" x-model="variant.purchase_price"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Цены по каналам - временно скрыто (функционал в разработке) --}}
                </section>



                <!-- Navigation -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div class="flex gap-2">
                        <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="prevStep" :disabled="step===1">← Назад</button>
                        <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="nextStep" :disabled="step===steps.length">Далее →</button>
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl shadow-lg shadow-indigo-500/25 transition-all font-medium">Сохранить</button>
                </div>
            </form>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="productEditor()" x-cloak style="background: #f2f2f7;">
    <x-pwa-header title="{{ $product->id ? 'Редактирование' : 'Новый товар' }}" :backUrl="'/products'">
        <button type="button" @click="submit()" class="native-header-btn text-blue-600 font-semibold" onclick="if(window.haptic) window.haptic.light()">
            Сохранить
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(100px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <form x-ref="form" method="POST" action="{{ $product->id ? route('web.products.update', $product) : route('web.products.store') }}" x-on:submit.prevent="submit">
            @csrf
            @if($product->id) @method('PUT') @endif

            <input type="hidden" name="options" x-ref="optionsInput">
            <input type="hidden" name="variants" x-ref="variantsInput">
            <input type="hidden" name="images" x-ref="imagesInput">
            <input type="hidden" name="attributes_product" x-ref="attributesProductInput">
            <input type="hidden" name="attributes_variants" x-ref="attributesVariantsInput">
            <input type="hidden" name="channel_settings" x-ref="channelSettingsInput">
            <input type="hidden" name="channel_variants" x-ref="channelVariantsInput">

            {{-- Step Indicator --}}
            <div class="px-4 py-4">
                <div class="flex items-center justify-between mb-4">
                    <template x-for="(stepName, idx) in ['Основное', 'Опции', 'Варианты', 'Изображения']" :key="idx">
                        <button type="button" @click="currentStep = idx + 1"
                                class="flex-1 py-2 text-xs font-medium text-center border-b-2 transition-colors"
                                :class="currentStep === idx + 1 ? 'border-blue-600 text-blue-600' : 'border-gray-200 text-gray-500'"
                                x-text="stepName"></button>
                    </template>
                </div>
            </div>

            {{-- Step 1: Basic Info --}}
            <div x-show="currentStep === 1" class="px-4 space-y-4">
                <div class="native-card space-y-4">
                    <div>
                        <label class="native-caption">Название товара *</label>
                        <input type="text" name="product[name]" x-model="product.name" class="native-input mt-1" placeholder="Введите название" required>
                    </div>

                    <div>
                        <label class="native-caption">Артикул (SKU)</label>
                        <input type="text" name="product[sku]" x-model="product.sku" class="native-input mt-1" placeholder="Артикул товара">
                    </div>

                    <div>
                        <label class="native-caption">Описание</label>
                        <textarea name="product[description]" x-model="product.description" class="native-input mt-1" rows="4" placeholder="Описание товара"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="native-caption">Цена ({{ $currency ?? 'UZS' }})</label>
                            <input type="number" name="product[base_price]" x-model="product.base_price" class="native-input mt-1" step="0.01" placeholder="0.00">
                        </div>
                        <div>
                            <label class="native-caption">Старая цена ({{ $currency ?? 'UZS' }})</label>
                            <input type="number" name="product[old_price]" x-model="product.old_price" class="native-input mt-1" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <button type="button" @click="currentStep = 2" class="native-btn w-full">Далее →</button>
            </div>

            {{-- Step 2: Options (simplified) --}}
            <div x-show="currentStep === 2" class="px-4 space-y-4">
                <div class="native-card">
                    <p class="native-body font-semibold mb-3">Размеры</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="size in globalSizes" :key="size.id">
                            <label class="inline-flex items-center px-3 py-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="selectedSizes.includes(size.code) ? 'bg-blue-100 border-blue-400 text-blue-700' : 'bg-white border-gray-200'">
                                <input type="checkbox" class="sr-only" :value="size.code" x-model="selectedSizes">
                                <span class="text-sm" x-text="size.value"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="native-card">
                    <p class="native-body font-semibold mb-3">Цвета</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="color in globalColors" :key="color.id">
                            <label class="inline-flex items-center px-3 py-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="selectedColors.includes(color.code) ? 'bg-blue-100 border-blue-400 text-blue-700' : 'bg-white border-gray-200'">
                                <input type="checkbox" class="sr-only" :value="color.code" x-model="selectedColors">
                                <span class="w-4 h-4 rounded-full mr-2 border" :style="`background-color: ${color.hex || '#ccc'}`"></span>
                                <span class="text-sm" x-text="color.value"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="button" @click="currentStep = 1" class="native-btn native-btn-secondary flex-1">← Назад</button>
                    <button type="button" @click="generateVariants(); currentStep = 3" class="native-btn flex-1">Далее →</button>
                </div>
            </div>

            {{-- Step 3: Variants --}}
            <div x-show="currentStep === 3" class="px-4 space-y-4">
                <div x-show="variants.length === 0" class="native-card text-center py-8">
                    <p class="native-caption">Нет вариантов. Выберите размеры или цвета.</p>
                </div>

                <template x-for="(variant, idx) in variants" :key="idx">
                    <div class="native-card">
                        <p class="native-body font-semibold mb-3" x-text="variant.name || `Вариант ${idx + 1}`"></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="native-caption">SKU</label>
                                <input type="text" x-model="variant.sku" class="native-input mt-1">
                            </div>
                            <div>
                                <label class="native-caption">Цена ({{ $currency ?? 'UZS' }})</label>
                                <input type="number" x-model="variant.price_default" class="native-input mt-1" step="0.01">
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex space-x-3">
                    <button type="button" @click="currentStep = 2" class="native-btn native-btn-secondary flex-1">← Назад</button>
                    <button type="button" @click="currentStep = 4" class="native-btn flex-1">Далее →</button>
                </div>
            </div>

            {{-- Step 4: Images --}}
            <div x-show="currentStep === 4" class="px-4 space-y-4">
                <div class="native-card">
                    <p class="native-body font-semibold mb-3">Изображения</p>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <template x-for="(img, idx) in images" :key="idx">
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden relative">
                                <img :src="img.file_path || 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect width=%22200%22 height=%22200%22 fill=%22%23f3f4f6%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22 font-size=%2214%22%3ENo image%3C/text%3E%3C/svg%3E'" class="w-full h-full object-cover">
                                <button type="button" @click="images.splice(idx, 1)" class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs">×</button>
                            </div>
                        </template>
                        <label class="aspect-square bg-gray-100 rounded-lg flex items-center justify-center cursor-pointer border-2 border-dashed border-gray-300">
                            <input type="file" accept="image/*" class="sr-only" @change="addImageFromFile($event.target.files[0])">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </label>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="button" @click="currentStep = 3" class="native-btn native-btn-secondary flex-1">← Назад</button>
                    <button type="submit" class="native-btn flex-1">Сохранить</button>
                </div>
            </div>
        </form>
    </main>
</div>
@endsection
