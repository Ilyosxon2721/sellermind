
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['rows' => 3]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['rows' => 3]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'bg-white rounded-2xl p-6 border border-gray-100 shadow-sm'])); ?>>
    
    <div class="h-6 bg-gray-200 rounded shimmer mb-4" style="width: 60%;"></div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < $rows; $i++): ?>
        <div class="space-y-3 mb-4">
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: <?php echo e(rand(70, 100)); ?>%;"></div>
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: <?php echo e(rand(50, 90)); ?>%;"></div>
        </div>
    <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
        <div class="h-8 bg-gray-200 rounded shimmer" style="width: 80px;"></div>
        <div class="h-8 bg-gray-200 rounded shimmer" style="width: 100px;"></div>
    </div>
</div>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\skeleton-card.blade.php ENDPATH**/ ?>