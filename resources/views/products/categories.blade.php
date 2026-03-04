@extends('layouts.app')

@section('content')
<style>[x-cloak]{display:none!important;}</style>

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50"
     x-data="categoriesPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
    <x-mobile-header />
    <x-pwa-top-navbar title="Категории" subtitle="Управление категориями товаров">
        <x-slot name="actions">
            <button @click="startCreate(); haptic()"
                    class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        </x-slot>
    </x-pwa-top-navbar>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">

        <!-- Header -->
        <header class="hidden lg:block bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">Категории</h1>
                    <p class="text-sm text-gray-500">Управление категориями товаров</p>
                </div>
                <button @click="startCreate()"
                        class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Добавить категорию</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto px-6 py-6 pwa-content-padding pwa-top-padding">
            <div class="flex flex-col lg:flex-row gap-6 h-full">

                <!-- Left Panel: Category Tree -->
                <div class="w-full lg:w-2/5">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 h-full flex flex-col">
                        <!-- Panel Header -->
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-800">Дерево категорий</h2>
                            <span x-show="!loading" x-cloak
                                  class="text-xs font-medium text-gray-400"
                                  x-text="totalCount + ' ' + pluralize(totalCount, 'категория', 'категории', 'категорий')"></span>
                        </div>

                        <!-- Tree Content -->
                        <div class="flex-1 overflow-y-auto p-3">
                            <!-- Loading State -->
                            <template x-if="loading">
                                <div class="space-y-3 p-2">
                                    <template x-for="i in 5" :key="'skel-'+i">
                                        <div class="animate-pulse flex items-center space-x-3">
                                            <div class="w-5 h-5 bg-gray-200 rounded"></div>
                                            <div class="flex-1 h-4 bg-gray-200 rounded"></div>
                                            <div class="w-8 h-4 bg-gray-200 rounded-full"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <template x-if="!loading && categories.length === 0">
                                <div class="flex flex-col items-center justify-center py-16 px-4">
                                    <div class="w-20 h-20 bg-indigo-50 rounded-2xl flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Категорий пока нет</p>
                                    <p class="text-xs text-gray-400 mb-4 text-center">Создайте первую категорию для организации товаров</p>
                                    <button @click="startCreate()"
                                            class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-sm font-medium hover:bg-indigo-100 transition-colors">
                                        Создать категорию
                                    </button>
                                </div>
                            </template>

                            <!-- Category Tree -->
                            <template x-if="!loading && categories.length > 0">
                                <div class="space-y-0.5">
                                    <template x-for="cat in categories" :key="cat.id">
                                        <div>
                                            <!-- Root Category Item -->
                                            <div class="flex items-center px-3 py-2.5 rounded-xl cursor-pointer transition-all group"
                                                 :class="selected && selected.id === cat.id
                                                     ? 'bg-indigo-50 border border-indigo-200'
                                                     : 'hover:bg-gray-50 border border-transparent'"
                                                 @click="selectCategory(cat)">
                                                <!-- Expand Toggle -->
                                                <button x-show="cat.children && cat.children.length > 0"
                                                        @click.stop="toggleExpand(cat.id)"
                                                        class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-gray-200/70 transition-colors mr-1.5 flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                         :class="expanded[cat.id] ? 'rotate-90' : ''"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="!cat.children || cat.children.length === 0" class="w-6 mr-1.5 flex-shrink-0"></div>

                                                <!-- Category Icon -->
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 flex-shrink-0"
                                                     :class="selected && selected.id === cat.id ? 'bg-indigo-100' : 'bg-gray-100 group-hover:bg-gray-200/70'">
                                                    <svg class="w-4 h-4"
                                                         :class="selected && selected.id === cat.id ? 'text-indigo-600' : 'text-gray-500'"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                                    </svg>
                                                </div>

                                                <!-- Name & Info -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-medium truncate"
                                                              :class="selected && selected.id === cat.id ? 'text-indigo-700' : 'text-gray-700'"
                                                              x-text="cat.name"></span>
                                                    </div>
                                                </div>

                                                <!-- Products Count -->
                                                <span x-show="cat.products_count !== undefined && cat.products_count > 0"
                                                      class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 ml-2"
                                                      :class="selected && selected.id === cat.id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                                                      x-text="cat.products_count"></span>

                                                <!-- Status Dot -->
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 ml-2"
                                                      :class="cat.is_active ? 'bg-green-400' : 'bg-gray-300'"
                                                      :title="cat.is_active ? 'Активна' : 'Неактивна'"></span>
                                            </div>

                                            <!-- Level 1 Children -->
                                            <div x-show="expanded[cat.id] && cat.children && cat.children.length > 0"
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                 x-transition:leave="transition ease-in duration-150"
                                                 x-transition:leave-start="opacity-100 translate-y-0"
                                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                                 class="pl-6">
                                                <template x-for="child in cat.children" :key="child.id">
                                                    <div>
                                                        <!-- Child Category Item -->
                                                        <div class="flex items-center px-3 py-2 rounded-xl cursor-pointer transition-all group"
                                                             :class="selected && selected.id === child.id
                                                                 ? 'bg-indigo-50 border border-indigo-200'
                                                                 : 'hover:bg-gray-50 border border-transparent'"
                                                             @click="selectCategory(child)">
                                                            <!-- Expand Toggle for grandchildren -->
                                                            <button x-show="child.children && child.children.length > 0"
                                                                    @click.stop="toggleExpand(child.id)"
                                                                    class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-gray-200/70 transition-colors mr-1.5 flex-shrink-0">
                                                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                                                                     :class="expanded[child.id] ? 'rotate-90' : ''"
                                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                                </svg>
                                                            </button>
                                                            <div x-show="!child.children || child.children.length === 0" class="w-6 mr-1.5 flex-shrink-0"></div>

                                                            <!-- Child Icon -->
                                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center mr-2.5 flex-shrink-0"
                                                                 :class="selected && selected.id === child.id ? 'bg-indigo-100' : 'bg-gray-100 group-hover:bg-gray-200/70'">
                                                                <svg class="w-3.5 h-3.5"
                                                                     :class="selected && selected.id === child.id ? 'text-indigo-600' : 'text-gray-400'"
                                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                                                </svg>
                                                            </div>

                                                            <!-- Name -->
                                                            <span class="text-sm truncate flex-1"
                                                                  :class="selected && selected.id === child.id ? 'text-indigo-700 font-medium' : 'text-gray-600'"
                                                                  x-text="child.name"></span>

                                                            <!-- Products Count -->
                                                            <span x-show="child.products_count !== undefined && child.products_count > 0"
                                                                  class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 ml-2"
                                                                  :class="selected && selected.id === child.id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                                                                  x-text="child.products_count"></span>

                                                            <!-- Status Dot -->
                                                            <span class="w-2 h-2 rounded-full flex-shrink-0 ml-2"
                                                                  :class="child.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>
                                                        </div>

                                                        <!-- Level 2 Grandchildren -->
                                                        <div x-show="expanded[child.id] && child.children && child.children.length > 0"
                                                             x-transition:enter="transition ease-out duration-200"
                                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                                             x-transition:enter-end="opacity-100 translate-y-0"
                                                             x-transition:leave="transition ease-in duration-150"
                                                             x-transition:leave-start="opacity-100 translate-y-0"
                                                             x-transition:leave-end="opacity-0 -translate-y-1"
                                                             class="pl-6">
                                                            <template x-for="grandchild in child.children" :key="grandchild.id">
                                                                <div class="flex items-center px-3 py-2 rounded-xl cursor-pointer transition-all group"
                                                                     :class="selected && selected.id === grandchild.id
                                                                         ? 'bg-indigo-50 border border-indigo-200'
                                                                         : 'hover:bg-gray-50 border border-transparent'"
                                                                     @click="selectCategory(grandchild)">
                                                                    <div class="w-6 mr-1.5 flex-shrink-0"></div>

                                                                    <!-- Grandchild Icon -->
                                                                    <div class="w-6 h-6 rounded-md flex items-center justify-center mr-2 flex-shrink-0"
                                                                         :class="selected && selected.id === grandchild.id ? 'bg-indigo-100' : 'bg-gray-50 group-hover:bg-gray-100'">
                                                                        <svg class="w-3 h-3"
                                                                             :class="selected && selected.id === grandchild.id ? 'text-indigo-500' : 'text-gray-400'"
                                                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                                        </svg>
                                                                    </div>

                                                                    <!-- Name -->
                                                                    <span class="text-sm truncate flex-1"
                                                                          :class="selected && selected.id === grandchild.id ? 'text-indigo-700 font-medium' : 'text-gray-500'"
                                                                          x-text="grandchild.name"></span>

                                                                    <!-- Products Count -->
                                                                    <span x-show="grandchild.products_count !== undefined && grandchild.products_count > 0"
                                                                          class="text-xs font-medium px-1.5 py-0.5 rounded-full flex-shrink-0 ml-2"
                                                                          :class="selected && selected.id === grandchild.id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                                                                          x-text="grandchild.products_count"></span>

                                                                    <!-- Status Dot -->
                                                                    <span class="w-2 h-2 rounded-full flex-shrink-0 ml-2"
                                                                          :class="grandchild.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Form -->
                <div class="w-full lg:w-3/5">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 h-full flex flex-col">

                        <!-- Empty State (nothing selected) -->
                        <template x-if="!selected && !creating">
                            <div class="flex-1 flex flex-col items-center justify-center py-20 px-6">
                                <div class="w-24 h-24 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl flex items-center justify-center mb-6">
                                    <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <p class="text-base font-medium text-gray-500 mb-1">Выберите категорию или создайте новую</p>
                                <p class="text-sm text-gray-400 mb-6 text-center">Нажмите на категорию слева для редактирования или используйте кнопку создания</p>
                                <button @click="startCreate()"
                                        class="px-5 py-2.5 bg-indigo-50 text-indigo-600 rounded-xl text-sm font-medium hover:bg-indigo-100 transition-colors flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Создать категорию</span>
                                </button>
                            </div>
                        </template>

                        <!-- Form (creating or editing) -->
                        <template x-if="selected || creating">
                            <div class="flex flex-col h-full">
                                <!-- Form Header -->
                                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800"
                                            x-text="creating ? 'Новая категория' : 'Редактирование категории'"></h2>
                                        <p class="text-xs text-gray-400 mt-0.5"
                                           x-show="selected"
                                           x-text="selected ? 'ID: ' + selected.id : ''"></p>
                                    </div>
                                    <button @click="selected = null; creating = false; errors = {}"
                                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600"
                                            title="Закрыть">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Form Body -->
                                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                                    <!-- Название -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            Название <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               x-model="form.name"
                                               placeholder="Например: Электроника"
                                               class="w-full px-4 py-2.5 border rounded-xl text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                               :class="errors.name ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-indigo-500'"
                                               @keydown.enter.prevent="save()">
                                        <p x-show="errors.name" x-text="errors.name?.[0] || errors.name" class="mt-1 text-xs text-red-500"></p>
                                    </div>

                                    <!-- Родительская категория -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Родительская категория</label>
                                        <select x-model="form.parent_id"
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                            <option :value="null">Нет (корневая категория)</option>
                                            <template x-for="opt in getParentOptions()" :key="opt.id">
                                                <option :value="opt.id"
                                                        :selected="form.parent_id == opt.id"
                                                        x-text="'\u00A0\u00A0'.repeat(opt.depth) + (opt.depth > 0 ? '\u2514\u00A0' : '') + opt.name"></option>
                                            </template>
                                        </select>
                                        <p x-show="errors.parent_id" x-text="errors.parent_id?.[0] || errors.parent_id" class="mt-1 text-xs text-red-500"></p>
                                    </div>

                                    <!-- Описание -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Описание</label>
                                        <textarea x-model="form.description"
                                                  rows="3"
                                                  placeholder="Краткое описание категории (необязательно)"
                                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 resize-none"></textarea>
                                    </div>

                                    <!-- Порядок сортировки -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Порядок сортировки</label>
                                        <input type="number"
                                               x-model.number="form.sort_order"
                                               min="0"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                        <p class="mt-1 text-xs text-gray-400">Чем меньше число, тем выше позиция</p>
                                    </div>

                                    <!-- Активна -->
                                    <div class="flex items-center justify-between py-2">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Активна</label>
                                            <p class="text-xs text-gray-400 mt-0.5">Неактивные категории скрыты от пользователей</p>
                                        </div>
                                        <button type="button"
                                                @click="form.is_active = !form.is_active"
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-200'"
                                                role="switch"
                                                :aria-checked="form.is_active">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                  :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Form Footer -->
                                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50 rounded-b-2xl">
                                    <!-- Delete Button (only when editing) -->
                                    <div>
                                        <button x-show="selected && !creating"
                                                @click="deleteConfirm = true"
                                                class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-xl text-sm font-medium transition-colors flex items-center space-x-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span>Удалить</span>
                                        </button>
                                    </div>

                                    <!-- Save Button -->
                                    <button @click="save()"
                                            :disabled="saving || !form.name.trim()"
                                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl text-sm font-medium transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteConfirm"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center"
         @keydown.escape.window="deleteConfirm = false">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteConfirm = false"></div>
        <!-- Modal -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="p-6">
                <!-- Icon -->
                <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <!-- Text -->
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Удалить категорию?</h3>
                <p class="text-sm text-gray-500 text-center">
                    Вы уверены, что хотите удалить категорию
                    <span class="font-medium text-gray-700" x-text="'&laquo;' + (selected ? selected.name : '') + '&raquo;'"></span>?
                    Это действие нельзя отменить.
                </p>
            </div>
            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-end space-x-3">
                <button @click="deleteConfirm = false"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                    Отмена
                </button>
                <button @click="deleteCategory()"
                        :disabled="deleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors flex items-center space-x-1.5 disabled:opacity-50">
                    <svg x-show="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!deleting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span x-text="deleting ? 'Удаление...' : 'Удалить'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" style="background: #f2f2f7;" x-data="categoriesPage()">
    <!-- Native Header -->
    <x-pwa-header title="Категории">
        <button @click="startCreate(); haptic()"
                class="native-header-btn">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll"
          style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        <!-- View Switcher: Tree / Form -->
        <div class="px-4 py-4">
            <div class="flex rounded-xl bg-gray-200/60 p-1">
                <button @click="mobileView = 'tree'"
                        class="flex-1 py-2 text-sm font-medium rounded-lg transition-all"
                        :class="mobileView === 'tree' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'">
                    Категории
                </button>
                <button @click="mobileView = 'form'"
                        class="flex-1 py-2 text-sm font-medium rounded-lg transition-all"
                        :class="mobileView === 'form' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'">
                    <span x-text="creating ? 'Создание' : (selected ? 'Редактирование' : 'Форма')"></span>
                </button>
            </div>
        </div>

        <!-- Tree View (mobile) -->
        <div x-show="mobileView === 'tree'" class="px-4 pb-4">
            <!-- Loading -->
            <template x-if="loading">
                <div class="native-card space-y-4 p-4">
                    <template x-for="i in 5" :key="'mskel-'+i">
                        <div class="animate-pulse flex items-center space-x-3">
                            <div class="w-5 h-5 bg-gray-200 rounded"></div>
                            <div class="flex-1 h-4 bg-gray-200 rounded"></div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && categories.length === 0">
                <div class="native-card text-center py-12">
                    <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Категорий пока нет</p>
                    <p class="text-xs text-gray-400 mb-4">Создайте первую категорию</p>
                    <button @click="startCreate(); mobileView = 'form'"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium">
                        Создать категорию
                    </button>
                </div>
            </template>

            <!-- Category List -->
            <template x-if="!loading && categories.length > 0">
                <div class="space-y-2">
                    <template x-for="cat in categories" :key="'m-'+cat.id">
                        <div class="native-card overflow-hidden">
                            <!-- Root Category -->
                            <div class="flex items-center p-3"
                                 @click="selectCategory(cat); mobileView = 'form'"
                                 :class="selected && selected.id === cat.id ? 'bg-blue-50' : ''">
                                <button x-show="cat.children && cat.children.length > 0"
                                        @click.stop="toggleExpand(cat.id)"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg mr-2 flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                         :class="expanded[cat.id] ? 'rotate-90' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                                <div x-show="!cat.children || cat.children.length === 0" class="w-8 mr-2 flex-shrink-0"></div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="cat.name"></p>
                                </div>

                                <span x-show="cat.products_count > 0"
                                      class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full mr-2"
                                      x-text="cat.products_count"></span>

                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                      :class="cat.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>

                                <svg class="w-4 h-4 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            <!-- Children (mobile) -->
                            <div x-show="expanded[cat.id] && cat.children && cat.children.length > 0"
                                 class="border-t border-gray-100">
                                <template x-for="child in cat.children" :key="'mc-'+child.id">
                                    <div>
                                        <div class="flex items-center p-3 pl-10"
                                             @click="selectCategory(child); mobileView = 'form'"
                                             :class="selected && selected.id === child.id ? 'bg-blue-50' : ''">
                                            <button x-show="child.children && child.children.length > 0"
                                                    @click.stop="toggleExpand(child.id)"
                                                    class="w-6 h-6 flex items-center justify-center rounded mr-2 flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                                                     :class="expanded[child.id] ? 'rotate-90' : ''"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                            <div x-show="!child.children || child.children.length === 0" class="w-6 mr-2 flex-shrink-0"></div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-600 truncate" x-text="child.name"></p>
                                            </div>

                                            <span x-show="child.products_count > 0"
                                                  class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full mr-2"
                                                  x-text="child.products_count"></span>

                                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                  :class="child.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>

                                            <svg class="w-4 h-4 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>

                                        <!-- Grandchildren (mobile) -->
                                        <template x-if="expanded[child.id] && child.children && child.children.length > 0">
                                            <div class="border-t border-gray-50">
                                                <template x-for="gc in child.children" :key="'mgc-'+gc.id">
                                                    <div class="flex items-center p-3 pl-16"
                                                         @click="selectCategory(gc); mobileView = 'form'"
                                                         :class="selected && selected.id === gc.id ? 'bg-blue-50' : ''">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm text-gray-500 truncate" x-text="gc.name"></p>
                                                        </div>

                                                        <span x-show="gc.products_count > 0"
                                                              class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full mr-2"
                                                              x-text="gc.products_count"></span>

                                                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                              :class="gc.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>

                                                        <svg class="w-4 h-4 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Form View (mobile) -->
        <div x-show="mobileView === 'form'" class="px-4 pb-4">
            <!-- Nothing Selected -->
            <template x-if="!selected && !creating">
                <div class="native-card text-center py-12">
                    <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Выберите категорию</p>
                    <p class="text-xs text-gray-400 mb-4">Перейдите на вкладку "Категории" и выберите элемент</p>
                    <button @click="startCreate()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium">
                        Создать новую
                    </button>
                </div>
            </template>

            <!-- Form -->
            <template x-if="selected || creating">
                <div class="space-y-4">
                    <div class="native-card p-4 space-y-4">
                        <h3 class="text-base font-semibold text-gray-800"
                            x-text="creating ? 'Новая категория' : 'Редактирование'"></h3>

                        <!-- Название -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
                            <input type="text"
                                   x-model="form.name"
                                   placeholder="Название категории"
                                   class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                   :class="errors.name ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                            <p x-show="errors.name" x-text="errors.name?.[0] || errors.name" class="mt-1 text-xs text-red-500"></p>
                        </div>

                        <!-- Родительская категория -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Родительская категория</label>
                            <select x-model="form.parent_id"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option :value="null">Нет (корневая)</option>
                                <template x-for="opt in getParentOptions()" :key="'mopt-'+opt.id">
                                    <option :value="opt.id"
                                            :selected="form.parent_id == opt.id"
                                            x-text="'\u00A0\u00A0'.repeat(opt.depth) + (opt.depth > 0 ? '\u2514\u00A0' : '') + opt.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Описание -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                            <textarea x-model="form.description"
                                      rows="3"
                                      placeholder="Необязательно"
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 resize-none"></textarea>
                        </div>

                        <!-- Порядок сортировки -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Порядок сортировки</label>
                            <input type="number"
                                   x-model.number="form.sort_order"
                                   min="0"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        </div>

                        <!-- Активна -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Активна</span>
                            <button type="button"
                                    @click="form.is_active = !form.is_active"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                                    :class="form.is_active ? 'bg-blue-600' : 'bg-gray-200'"
                                    role="switch">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                      :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-3">
                        <button x-show="selected && !creating"
                                @click="deleteConfirm = true"
                                class="flex-shrink-0 px-4 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl text-sm font-medium">
                            Удалить
                        </button>
                        <button @click="save()"
                                :disabled="saving || !form.name.trim()"
                                class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center space-x-2">
                            <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <!-- Delete Confirmation Modal (PWA) -->
    <div x-show="deleteConfirm"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end justify-center"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="absolute inset-0 bg-black/40" @click="deleteConfirm = false"></div>
        <div class="relative bg-white rounded-t-2xl w-full max-w-lg p-6 pb-8"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Удалить категорию?</h3>
            <p class="text-sm text-gray-500 text-center mb-6"
               x-text="'Вы уверены, что хотите удалить категорию \u00AB' + (selected ? selected.name : '') + '\u00BB?'"></p>
            <div class="space-y-2">
                <button @click="deleteCategory()"
                        :disabled="deleting"
                        class="w-full py-3 bg-red-600 text-white rounded-xl text-sm font-medium disabled:opacity-50">
                    <span x-text="deleting ? 'Удаление...' : 'Удалить'"></span>
                </button>
                <button @click="deleteConfirm = false"
                        class="w-full py-3 bg-gray-100 text-gray-700 rounded-xl text-sm font-medium">
                    Отмена
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function categoriesPage() {
    return {
        categories: [],
        loading: true,
        selected: null,
        creating: false,
        saving: false,
        deleting: false,
        deleteConfirm: false,
        mobileView: 'tree',
        errors: {},
        expanded: {},
        form: {
            name: '',
            parent_id: null,
            description: '',
            sort_order: 0,
            is_active: true,
        },

        get totalCount() {
            let count = 0;
            const countRecursive = (cats) => {
                for (const cat of cats) {
                    count++;
                    if (cat.children && cat.children.length) {
                        countRecursive(cat.children);
                    }
                }
            };
            countRecursive(this.categories);
            return count;
        },

        async init() {
            await this.loadCategories();
        },

        async loadCategories() {
            this.loading = true;
            try {
                const res = await fetch('/api/categories', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                });
                if (!res.ok) throw new Error('Failed to load categories');
                const json = await res.json();
                this.categories = json.data || json || [];
            } catch (e) {
                console.error('Error loading categories:', e);
                this.showToast('error', 'Не удалось загрузить категории');
            } finally {
                this.loading = false;
            }
        },

        selectCategory(category) {
            this.selected = JSON.parse(JSON.stringify(category));
            this.creating = false;
            this.errors = {};
            this.form = {
                name: category.name || '',
                parent_id: category.parent_id || null,
                description: category.description || '',
                sort_order: category.sort_order || 0,
                is_active: category.is_active !== undefined ? category.is_active : true,
            };
        },

        startCreate(parentId = null) {
            this.selected = null;
            this.creating = true;
            this.errors = {};
            this.form = {
                name: '',
                parent_id: parentId,
                description: '',
                sort_order: 0,
                is_active: true,
            };
        },

        async save() {
            if (!this.form.name.trim()) {
                this.errors = { name: ['Название обязательно для заполнения'] };
                return;
            }

            this.saving = true;
            this.errors = {};

            const url = this.creating
                ? '/api/categories'
                : '/api/categories/' + this.selected.id;
            const method = this.creating ? 'POST' : 'PUT';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                    body: JSON.stringify({
                        name: this.form.name.trim(),
                        parent_id: this.form.parent_id || null,
                        description: this.form.description.trim() || null,
                        sort_order: this.form.sort_order || 0,
                        is_active: this.form.is_active,
                    }),
                });

                if (res.status === 422) {
                    const errorData = await res.json();
                    this.errors = errorData.errors || {};
                    this.showToast('error', 'Проверьте правильность заполнения формы');
                    return;
                }

                if (!res.ok) {
                    throw new Error('Save failed');
                }

                const savedCategory = await res.json();
                const message = this.creating ? 'Категория создана' : 'Категория обновлена';

                await this.loadCategories();

                // Re-select updated category if editing
                if (!this.creating && savedCategory.data) {
                    this.findAndSelect(savedCategory.data.id);
                } else if (savedCategory.data) {
                    this.findAndSelect(savedCategory.data.id);
                } else {
                    this.selected = null;
                    this.creating = false;
                }

                this.showToast('success', message);
            } catch (e) {
                console.error('Error saving category:', e);
                this.showToast('error', 'Не удалось сохранить категорию');
            } finally {
                this.saving = false;
            }
        },

        async deleteCategory() {
            if (!this.selected) return;

            this.deleting = true;
            try {
                const res = await fetch('/api/categories/' + this.selected.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                });

                if (res.status === 422) {
                    const errorData = await res.json();
                    const msg = errorData.message || 'Невозможно удалить: категория содержит товары или подкатегории';
                    this.showToast('error', msg);
                    this.deleteConfirm = false;
                    this.deleting = false;
                    return;
                }

                if (!res.ok) {
                    throw new Error('Delete failed');
                }

                this.deleteConfirm = false;
                this.selected = null;
                this.creating = false;
                this.errors = {};
                await this.loadCategories();
                this.showToast('success', 'Категория удалена');

                // Switch back to tree on mobile
                this.mobileView = 'tree';
            } catch (e) {
                console.error('Error deleting category:', e);
                this.showToast('error', 'Не удалось удалить категорию');
            } finally {
                this.deleting = false;
            }
        },

        toggleExpand(id) {
            this.expanded[id] = !this.expanded[id];
        },

        getParentOptions() {
            const result = [];
            const selectedId = this.selected ? this.selected.id : null;

            const collectDescendantIds = (cat) => {
                const ids = [cat.id];
                if (cat.children && cat.children.length) {
                    for (const child of cat.children) {
                        ids.push(...collectDescendantIds(child));
                    }
                }
                return ids;
            };

            // Get IDs to exclude (self + descendants)
            let excludeIds = new Set();
            if (selectedId) {
                const findCat = (cats) => {
                    for (const cat of cats) {
                        if (cat.id === selectedId) return cat;
                        if (cat.children && cat.children.length) {
                            const found = findCat(cat.children);
                            if (found) return found;
                        }
                    }
                    return null;
                };
                const selfCat = findCat(this.categories);
                if (selfCat) {
                    excludeIds = new Set(collectDescendantIds(selfCat));
                }
            }

            const flatten = (cats, depth) => {
                for (const cat of cats) {
                    if (excludeIds.has(cat.id)) continue;
                    // Only allow selecting root or 1-level-deep as parent (max 2 levels nesting)
                    if (depth <= 1) {
                        result.push({ id: cat.id, name: cat.name, depth: depth });
                    }
                    if (cat.children && cat.children.length) {
                        flatten(cat.children, depth + 1);
                    }
                }
            };

            flatten(this.categories, 0);
            return result;
        },

        findAndSelect(id) {
            const find = (cats) => {
                for (const cat of cats) {
                    if (cat.id === id) {
                        this.selectCategory(cat);
                        return true;
                    }
                    if (cat.children && cat.children.length) {
                        if (find(cat.children)) return true;
                    }
                }
                return false;
            };
            find(this.categories);
        },

        pluralize(n, one, few, many) {
            const mod10 = n % 10;
            const mod100 = n % 100;
            if (mod10 === 1 && mod100 !== 11) return one;
            if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few;
            return many;
        },

        getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.content : '';
        },

        showToast(type, message) {
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { type: type, message: message }
            }));
        },

        haptic() {
            if (window.haptic) window.haptic.light();
        },
    };
}
</script>
@endsection
