<?php $__env->startSection('content'); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($accountMarketplace ?? '') === 'uzum'): ?>
        <?php echo $__env->make('pages.marketplace.partials.products_uzum', ['accountId' => $accountId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php elseif(($accountMarketplace ?? '') === 'ym' || ($accountMarketplace ?? '') === 'yandex_market'): ?>
        <?php echo $__env->make('pages.marketplace.partials.products_ym', ['accountId' => $accountId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php elseif(($accountMarketplace ?? '') === 'ozon'): ?>
        <?php echo $__env->make('pages.marketplace.partials.products_ozon', ['accountId' => $accountId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('pages.marketplace.partials.products_wb', ['accountId' => $accountId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\marketplace\products.blade.php ENDPATH**/ ?>