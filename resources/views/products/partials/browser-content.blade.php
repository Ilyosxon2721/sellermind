<!-- Filters -->
<form method="GET" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-ref="filterForm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
        <a href="{{ route('web.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Сбросить</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Название или артикул"
                   @change="$refs.filterForm.submit()">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
            <select name="category_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" @change="$refs.filterForm.submit()">
                <option value="">Все категории</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="is_archived" value="1"
                       @change="$refs.filterForm.submit()"
                       @checked($filters['is_archived'] ?? false)
                       class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Показывать архив</span>
            </label>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium">
                Применить
            </button>
        </div>
    </div>
</form>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $products->total() }}</div>
            <div class="text-sm text-gray-500">Всего товаров</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $products->where('is_active', true)->count() }}</div>
            <div class="text-sm text-gray-500">Активных</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $categories->count() }}</div>
            <div class="text-sm text-gray-500">Категорий</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $products->sum('variants_count') }}</div>
            <div class="text-sm text-gray-500">Вариантов</div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Превью</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Название</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Артикул</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Категория</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Варианты</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Каналы</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Обновлено</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                    @php
                        $image = $product->mainImage ?? $product->images->first();
                        $channels = collect($product->channelSettings ?? [])->keyBy(fn($s) => $s->channel?->code ?? $s->channel_id);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            @if($image?->file_path)
                                <img src="{{ $image->file_path }}" alt="" class="h-14 w-14 object-cover rounded-xl">
                            @else
                                <div class="h-14 w-14 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 text-xs">нет</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500">#{{ $product->id }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $product->article }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ optional($categories->firstWhere('id', $product->category_id))->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">{{ $product->variants_count }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1.5">
                                @foreach(['WB' => 'WB', 'OZON' => 'Ozon', 'YM' => 'YM', 'UZUM' => 'Uzum'] as $code => $label)
                                    @php $status = optional($channels->get($code))->status; @endphp
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium
                                        @if($status === 'published') bg-green-100 text-green-700
                                        @elseif($status === 'pending') bg-amber-100 text-amber-700
                                        @elseif($status === 'error') bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-400 @endif">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ optional($product->updated_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('web.products.edit', $product) }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                                    Редактировать
                                </a>
                                <form method="POST" action="{{ route('web.products.destroy', $product) }}"
                                      onsubmit="return confirm('Удалить товар «{{ addslashes($product->name) }}»?')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 rounded-lg text-sm transition-colors">
                                        Удалить
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="text-gray-500">Товары не найдены</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t bg-gray-50">
        {{ $products->links() }}
    </div>
</div>
