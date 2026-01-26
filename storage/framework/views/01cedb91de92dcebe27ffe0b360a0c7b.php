<!-- Bulk Operations Modals and Components -->

<!-- Import Modal -->
<div x-show="showImportModal"
     x-cloak
     @click.self="showImportModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden"
         @click.stop>
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Импорт товаров</h3>
            <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <!-- Step 1: Upload File -->
            <div x-show="importStep === 1">
                <p class="text-gray-600 mb-4">
                    Загрузите CSV файл с обновлёнными данными товаров. Используйте экспорт как шаблон.
                </p>

                <!-- Drag & Drop Zone -->
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-indigo-400 transition"
                     :class="{'border-indigo-500 bg-indigo-50': isDragging}"
                     @drop.prevent="handleDrop"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false">
                    <input type="file"
                           ref="importFileInput"
                           @change="handleFileSelect"
                           accept=".csv,.txt"
                           class="hidden">

                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>

                    <template x-if="!importFile">
                        <div>
                            <p class="text-gray-700 font-medium mb-2">
                                Перетащите CSV файл сюда
                            </p>
                            <p class="text-gray-500 text-sm mb-4">или</p>
                            <button @click="$refs.importFileInput.click()"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                                Выбрать файл
                            </button>
                            <p class="text-gray-400 text-xs mt-3">
                                Максимальный размер: 10 MB
                            </p>
                        </div>
                    </template>

                    <template x-if="importFile">
                        <div class="flex items-center justify-center space-x-3">
                            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-left">
                                <p class="font-medium text-gray-900" x-text="importFile.name"></p>
                                <p class="text-sm text-gray-500" x-text="formatFileSize(importFile.size)"></p>
                            </div>
                            <button @click="importFile = null" class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Instructions -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Важно
                    </h4>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Не изменяйте ID товаров и вариантов</li>
                        <li>• Не удаляйте и не меняйте порядок столбцов</li>
                        <li>• Используйте точку с запятой (;) как разделитель</li>
                        <li>• Сохраняйте файл в кодировке UTF-8</li>
                    </ul>
                </div>
            </div>

            <!-- Step 2: Preview -->
            <div x-show="importStep === 2">
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Предпросмотр изменений</h4>
                    <p class="text-gray-600 text-sm">
                        Проверьте изменения перед применением
                    </p>
                </div>

                <!-- Summary -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600 mb-1">Всего строк</p>
                        <p class="text-2xl font-bold text-blue-900" x-text="importPreview?.total_rows || 0"></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600 mb-1">Изменений</p>
                        <p class="text-2xl font-bold text-green-900" x-text="importPreview?.changes_count || 0"></p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-sm text-red-600 mb-1">Ошибок</p>
                        <p class="text-2xl font-bold text-red-900" x-text="importPreview?.errors_count || 0"></p>
                    </div>
                </div>

                <!-- Errors -->
                <div x-show="importPreview?.errors_count > 0" class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h5 class="font-semibold text-red-900 mb-2">Ошибки</h5>
                        <ul class="text-sm text-red-700 space-y-1 max-h-32 overflow-y-auto">
                            <template x-for="error in importPreview?.errors" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- Changes -->
                <div x-show="importPreview?.changes_count > 0">
                    <h5 class="font-semibold text-gray-900 mb-3">Изменения (первые 20)</h5>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <template x-for="change in importPreview?.preview" :key="change.row">
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900" x-text="change.product_name"></span>
                                    <span class="text-xs text-gray-500">SKU: <span x-text="change.sku"></span></span>
                                </div>
                                <div class="space-y-1">
                                    <template x-for="(value, key) in change.changes" :key="key">
                                        <div class="text-xs flex items-center space-x-2">
                                            <span class="text-gray-600 capitalize" x-text="key.replace('_', ' ')"></span>:
                                            <span class="text-red-600 line-through" x-text="value.old"></span>
                                            <span class="text-gray-400">→</span>
                                            <span class="text-green-600 font-medium" x-text="value.new"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="importLoading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <p class="mt-4 text-gray-600">Обработка файла...</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <button @click="showImportModal = false"
                    class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">
                Отмена
            </button>
            <div class="flex space-x-3">
                <button x-show="importStep === 1"
                        @click="previewImport()"
                        :disabled="!importFile || importLoading"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Далее
                </button>
                <template x-if="importStep === 2">
                    <div class="flex space-x-3">
                        <button @click="importStep = 1"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                            Назад
                        </button>
                        <button @click="applyImport()"
                                :disabled="importPreview?.changes_count === 0 || importPreview?.errors_count > 0 || importLoading"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Применить изменения
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div x-show="selectedVariants.length > 0"
     x-cloak
     x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 px-6 py-4 flex items-center space-x-4">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-gray-900">
                Выбрано: <span x-text="selectedVariants.length"></span>
            </span>
        </div>

        <div class="w-px h-6 bg-gray-300"></div>

        <div class="flex items-center space-x-2">
            <button @click="bulkAction('activate')"
                    class="px-3 py-1.5 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                Активировать
            </button>
            <button @click="bulkAction('deactivate')"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                Деактивировать
            </button>
            <button @click="showBulkPriceModal = true"
                    class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                Изменить цены
            </button>
        </div>

        <div class="w-px h-6 bg-gray-300"></div>

        <button @click="clearSelection()"
                class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<!-- Bulk Price Change Modal -->
<div x-show="showBulkPriceModal"
     x-cloak
     @click.self="showBulkPriceModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4"
         @click.stop>
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Массовое изменение цен</h3>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Розничная цена
                </label>
                <input type="number"
                       x-model="bulkPriceForm.retail_price"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="Оставьте пустым чтобы не менять">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Закупочная цена
                </label>
                <input type="number"
                       x-model="bulkPriceForm.purchase_price"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="Оставьте пустым чтобы не менять">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Старая цена
                </label>
                <input type="number"
                       x-model="bulkPriceForm.old_price"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                       placeholder="Оставьте пустым чтобы не менять">
            </div>

            <p class="text-sm text-gray-500">
                Изменения будут применены к <span class="font-semibold" x-text="selectedVariants.length"></span> товарам
            </p>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button @click="showBulkPriceModal = false"
                    class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">
                Отмена
            </button>
            <button @click="bulkUpdatePrices()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                Применить
            </button>
        </div>
    </div>
</div>

<style>
[x-cloak] {
    display: none !important;
}
</style>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\product-bulk-operations.blade.php ENDPATH**/ ?>