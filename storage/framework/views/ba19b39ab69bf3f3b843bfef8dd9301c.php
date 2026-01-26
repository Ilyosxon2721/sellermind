<?php $__env->startSection('content'); ?>
<style>[x-cloak]{display:none!important;}</style>


<?php
    $toastMessage = session('success') ?? (request('saved') === 'created' ? 'Товар успешно создан!' : (request('saved') === 'updated' ? 'Товар успешно обновлён!' : null));
    $toastError = session('error');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($toastMessage || $toastError): ?>
<div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
     class="fixed top-4 right-4 z-[9999] max-w-md">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($toastMessage): ?>
    <div class="bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-green-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="font-medium"><?php echo e($toastMessage); ?></span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($toastError): ?>
    <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-red-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span class="font-medium"><?php echo e($toastError); ?></span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50">
    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal415cf90115c14f51a96642adfc4a4cc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal415cf90115c14f51a96642adfc4a4cc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.mobile-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mobile-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal415cf90115c14f51a96642adfc4a4cc2)): ?>
<?php $attributes = $__attributesOriginal415cf90115c14f51a96642adfc4a4cc2; ?>
<?php unset($__attributesOriginal415cf90115c14f51a96642adfc4a4cc2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal415cf90115c14f51a96642adfc4a4cc2)): ?>
<?php $component = $__componentOriginal415cf90115c14f51a96642adfc4a4cc2; ?>
<?php unset($__componentOriginal415cf90115c14f51a96642adfc4a4cc2); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal1d47d88f11043f170d38bb1a1e5e859d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-top-navbar','data' => ['title' => 'Товары','subtitle' => 'Список товаров компании']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-top-navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Товары','subtitle' => 'Список товаров компании']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('web.products.create')); ?>"
               class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d)): ?>
<?php $attributes = $__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d; ?>
<?php unset($__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1d47d88f11043f170d38bb1a1e5e859d)): ?>
<?php $component = $__componentOriginal1d47d88f11043f170d38bb1a1e5e859d; ?>
<?php unset($__componentOriginal1d47d88f11043f170d38bb1a1e5e859d); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="hidden lg:block bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Товары</h1>
                    <p class="text-sm text-gray-500">Список товаров компании с фильтрами и статусами</p>
                </div>
                <a href="<?php echo e(route('web.products.create')); ?>"
                   class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Добавить товар</span>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6 pwa-content-padding pwa-top-padding"
              x-data="{ refreshPage() { window.location.reload(); } }"
              x-pull-to-refresh="refreshPage">
            <?php echo $__env->make('products.partials.browser-content', ['products' => $products, 'categories' => $categories, 'filters' => $filters], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </main>
    </div>
</div>


<div class="pwa-only min-h-screen" style="background: #f2f2f7;">
    
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Товары']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Товары']); ?>
        
        <a href="<?php echo e(route('web.products.create')); ?>"
           class="native-header-btn"
           onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $attributes = $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $component = $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>

    
    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;"
          x-data="{ refreshPage() { window.location.reload(); } }"
          x-pull-to-refresh="refreshPage">

        
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?php echo e($products->total()); ?></p>
                        <p class="native-caption">Всего</p>
                    </div>
                </div>
            </div>

            
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?php echo e($products->where('is_active', true)->count()); ?></p>
                        <p class="native-caption">Активных</p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="px-4 pb-4">
            <form method="GET" x-ref="filterForm">
                <div class="native-card space-y-3">
                    <label class="block">
                        <span class="native-caption">Поиск</span>
                        <input type="text" name="search" value="<?php echo e($filters['search'] ?? ''); ?>"
                               class="native-input mt-1"
                               placeholder="Название или артикул"
                               @change="$refs.filterForm.submit()">
                    </label>

                    <label class="block">
                        <span class="native-caption">Категория</span>
                        <select name="category_id" class="native-input mt-1" @change="$refs.filterForm.submit()">
                            <option value="">Все категории</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->id); ?>" <?php if(($filters['category_id'] ?? null) == $category->id): echo 'selected'; endif; ?>>
                                    <?php echo e($category->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </label>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <label class="flex items-center space-x-2">
                            <div class="native-switch <?php if($filters['is_archived'] ?? false): ?> active <?php endif; ?>"
                                 onclick="this.classList.toggle('active'); this.closest('form').querySelector('input[name=is_archived]').checked = this.classList.contains('active'); this.closest('form').submit();">
                            </div>
                            <input type="checkbox" name="is_archived" value="1" <?php if($filters['is_archived'] ?? false): echo 'checked'; endif; ?> class="hidden">
                            <span class="native-caption">Показать архив</span>
                        </label>
                        <a href="<?php echo e(route('web.products.index')); ?>" class="text-blue-600 text-sm font-semibold">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>

        
        <div class="px-4 space-y-3 pb-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $image = $product->mainImage ?? $product->images->first();
                    $channels = collect($product->channelSettings ?? [])->keyBy(fn($s) => $s->channel?->code ?? $s->channel_id);
                ?>
                <a href="<?php echo e(route('web.products.edit', $product)); ?>"
                   class="native-card block native-pressable"
                   onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex space-x-3">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image?->file_path): ?>
                            <img src="<?php echo e($image->file_path); ?>" alt="" class="h-20 w-20 object-cover rounded-xl flex-shrink-0">
                        <?php else: ?>
                            <div class="h-20 w-20 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 flex-shrink-0">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div class="flex-1 min-w-0">
                            <h3 class="native-body font-semibold truncate"><?php echo e($product->name); ?></h3>
                            <p class="native-caption mt-0.5"><?php echo e($product->article); ?></p>
                            <p class="native-caption mt-0.5">
                                <?php echo e(optional($categories->firstWhere('id', $product->category_id))->name ?? '—'); ?>

                            </p>

                            
                            <div class="flex gap-1 mt-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['WB' => 'WB', 'OZON' => 'Ozon', 'YM' => 'YM', 'UZUM' => 'Uzum']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $status = optional($channels->get($code))->status; ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium
                                        <?php if($status === 'published'): ?> bg-green-100 text-green-700
                                        <?php elseif($status === 'pending'): ?> bg-amber-100 text-amber-700
                                        <?php elseif($status === 'error'): ?> bg-red-100 text-red-700
                                        <?php else: ?> bg-gray-100 text-gray-400 <?php endif; ?>">
                                        <?php echo e($label); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="native-card text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500 mb-2">Товары не найдены</p>
                    <p class="native-caption">Попробуйте изменить фильтры</p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($products->hasPages()): ?>
        <div class="px-4 pb-4">
            <div class="native-card">
                <?php echo e($products->links()); ?>

            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>

    
    <a href="<?php echo e(route('web.products.create')); ?>"
       class="pwa-only fixed bottom-24 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform"
       style="z-index: 40;"
       onclick="if(window.haptic) window.haptic.medium()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\products\index.blade.php ENDPATH**/ ?>