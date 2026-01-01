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

<div class="flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="productEditor()" x-cloak>

    <x-sidebar />


    <div class="flex-1 flex flex-col overflow-hidden">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                            <select name="product[category_id]" x-model="product.category_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Не выбрано</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
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
                            </div>
                        </div>

                        <!-- Colors Selection -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-gray-800">Цвета</h3>
                                <span class="text-sm text-gray-500">Выбрано: <span class="font-semibold text-indigo-600" x-text="selectedColors.length"></span></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
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
                            <button type="button" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/25 flex items-center gap-2" @click="addImage">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Добавить изображение
                            </button>
                        </div>
                        <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                            <p class="font-medium mb-1">💡 Загрузка изображений</p>
                            <p>Вы можете загрузить файл с компьютера или указать URL. Для каждого варианта (цвет/размер) можно добавить отдельные фото.</p>
                        </div>
                    </div>
                    
                    <!-- Images per variant -->
                    <div class="space-y-4">
                        <template x-for="(image, idx) in images" :key="idx">
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                    <!-- File upload or URL -->
                                    <div class="md:col-span-4 space-y-2">
                                        <label class="text-xs font-medium text-gray-600">Изображение</label>
                                        
                                        <!-- Preview -->
                                        <template x-if="image.file_path">
                                            <div class="relative">
                                                <img :src="image.file_path" class="w-full h-32 object-cover rounded-lg border" x-on:error="$el.style.display='none'">
                                            </div>
                                        </template>
                                        
                                        <!-- File input -->
                                        <div class="flex gap-2">
                                            <label class="flex-1 cursor-pointer">
                                                <input type="file" 
                                                       accept="image/*" 
                                                       class="hidden" 
                                                       @change="uploadImage($event, idx)">
                                                <div class="w-full px-3 py-2 border border-dashed border-indigo-300 bg-indigo-50 rounded-lg text-sm text-center text-indigo-600 hover:bg-indigo-100 transition">
                                                    📁 Выбрать файл
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <!-- URL input -->
                                        <input type="text" 
                                               class="w-full border rounded-lg px-3 py-2 text-sm" 
                                               x-model="image.file_path" 
                                               placeholder="или введите URL изображения">
                                    </div>
                                    
                                    <!-- Variant selector -->
                                    <div class="md:col-span-3">
                                        <label class="text-xs font-medium text-gray-600">Для варианта</label>
                                        <select class="mt-1 w-full border rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" x-model="image.variant_id">
                                            <option value="">📦 Общий (все варианты)</option>
                                            <template x-for="variant in variants" :key="variant.sku">
                                                <option :value="variant.id ?? variant.sku" x-text="'🏷️ ' + (variant.option_values_summary || variant.sku)"></option>
                                            </template>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Выберите вариант (размер/цвет)</p>
                                    </div>
                                    
                                    <!-- Alt text -->
                                    <div class="md:col-span-3">
                                        <label class="text-xs font-medium text-gray-600">Alt текст</label>
                                        <input type="text" 
                                               class="mt-1 w-full border rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500" 
                                               x-model="image.alt_text"
                                               placeholder="Описание для SEO">
                                    </div>
                                    
                                    <!-- Controls -->
                                    <div class="md:col-span-2 flex flex-col gap-2 items-end">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="w-4 h-4 rounded text-indigo-600" x-model="image.is_main">
                                            <span class="ml-2 text-sm">Главное</span>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" class="p-1.5 text-gray-500 hover:bg-gray-200 rounded" @click="moveImage(idx, -1)" title="Вверх">▲</button>
                                            <button type="button" class="p-1.5 text-gray-500 hover:bg-gray-200 rounded" @click="moveImage(idx, 1)" title="Вниз">▼</button>
                                            <button type="button" class="p-1.5 text-red-600 hover:bg-red-100 rounded" @click="removeImage(idx)" title="Удалить">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Empty state -->
                        <template x-if="images.length === 0">
                            <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl">
                                <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Нет изображений</p>
                                <button type="button" @click="addImage" class="mt-3 px-4 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    + Добавить первое изображение
                                </button>
                            </div>
                        </template>
                    </div>
                </section>


                <!-- Step 6: Prices -->
                <section x-show="step === 6" class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Базовые цены</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-xs text-gray-500 bg-gray-50"><th class="px-3 py-2 rounded-l-lg">Вариант</th><th class="px-3 py-2">Цена</th><th class="px-3 py-2">Старая цена</th><th class="px-3 py-2 rounded-r-lg">Закупка</th></tr></thead>
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

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
                        <div class="flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900">Цены по каналам</h2>
                            <div class="flex gap-2"><template x-for="channel in channels" :key="channel.code"><button type="button" class="text-xs text-indigo-600 underline" @click="copyBasePrices(channel.code)">Скопировать в <span x-text="channel.name"></span></button></template></div>
                        </div>
                        <template x-for="channel in channels" :key="channel.code">
                            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-800" x-text="channel.name"></span>
                                    <label class="inline-flex items-center"><input type="checkbox" class="w-4 h-4 rounded text-indigo-600" @change="toggleChannel(channel.code, $event.target.checked)" :checked="channelEnabled(channel.code)"><span class="ml-2 text-sm">Продавать</span></label>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead><tr class="text-left text-xs text-gray-500"><th class="px-2 py-1">Вариант</th><th class="px-2 py-1">Цена</th><th class="px-2 py-1">Старая</th><th class="px-2 py-1">External ID</th><th class="px-2 py-1">Статус</th></tr></thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="variant in variants" :key="variant.sku">
                                                <tr>
                                                    <td class="px-2 py-1 text-sm" x-text="variant.option_values_summary || variant.sku || 'Вариант'"></td>
                                                    <td class="px-2 py-1"><input type="number" step="0.01" class="w-20 border rounded px-2 py-1 text-sm" x-model="channelVariantValue(channel.code, variant, 'price')"></td>
                                                    <td class="px-2 py-1"><input type="number" step="0.01" class="w-20 border rounded px-2 py-1 text-sm" x-model="channelVariantValue(channel.code, variant, 'old_price')"></td>
                                                    <td class="px-2 py-1"><input type="text" class="w-24 border rounded px-2 py-1 text-sm" x-model="channelVariantValue(channel.code, variant, 'external_offer_id')"></td>
                                                    <td class="px-2 py-1"><select class="w-24 border rounded px-2 py-1 text-sm" x-model="channelVariantValue(channel.code, variant, 'status')"><option value="draft">draft</option><option value="pending">pending</option><option value="published">published</option><option value="error">error</option></select></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
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

<script>
function productEditor() {
    const data = window.__productEditorData || {};
    const initial = data.initialState || {};
    const attributeDefs = data.attributesList || [];
    const publishUrl = data.publishUrl || '';
    const globalSizes = data.globalSizes || [];
    const globalColors = data.globalColors || [];
    
    return {
        step: 1,
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
            
            this.variants = [];
            
            for (const sizeCode of sizes) {
                const sizeObj = this.globalSizes.find(s => s.code === sizeCode);
                const sizeValue = sizeObj ? sizeObj.value : sizeCode;
                
                for (const colorCode of colors) {
                    const colorObj = this.globalColors.find(c => c.code === colorCode);
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
        addImage() { this.images.push({file_path: '', alt_text: '', is_main: this.images.length === 0, sort_order: this.images.length, variant_id: ''}); },
        removeImage(idx) { this.images.splice(idx, 1); },
        moveImage(idx, dir) { const target = idx + dir; if (target < 0 || target >= this.images.length) return; const tmp = this.images[target]; this.images[target] = this.images[idx]; this.images[idx] = tmp; },
        async uploadImage(event, idx) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Show loading state
            const originalPath = this.images[idx].file_path;
            this.images[idx].file_path = 'Загрузка...';
            
            const formData = new FormData();
            formData.append('image', file);
            
            // Get auth token from localStorage
            const tokenRaw = localStorage.getItem('_x_auth_token');
            const token = tokenRaw ? JSON.parse(tokenRaw) : null;
            
            try {
                const response = await fetch('/api/products/upload-image', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': token ? `Bearer ${token}` : '',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error('Сервер вернул неожиданный ответ. Попробуйте перезагрузить страницу.');
                }
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Ошибка загрузки');
                }
                
                this.images[idx].file_path = data.path || data.url;
                this.images[idx].alt_text = file.name.replace(/\.[^/.]+$/, '');
            } catch (e) {
                console.error('Upload error:', e);
                this.images[idx].file_path = originalPath;
                alert('❌ ' + (e.message || 'Ошибка загрузки файла'));
            }
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
@endsection
