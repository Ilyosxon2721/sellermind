<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чек #<?php echo e($sale->sale_number); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #333;
        }
        .receipt-info {
            margin-bottom: 10px;
        }
        .receipt-info p {
            margin: 2px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            font-size: 10px;
        }
        .items-table td {
            padding: 5px 0;
            border-bottom: 1px dashed #ccc;
            font-size: 11px;
        }
        .items-table .qty {
            text-align: center;
            width: 30px;
        }
        .items-table .price {
            text-align: right;
            width: 60px;
        }
        .items-table .total {
            text-align: right;
            width: 70px;
        }
        .totals {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .totals-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        .footer p {
            margin: 3px 0;
        }
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', monospace;
            font-size: 30px;
        }
        @media print {
            body {
                width: 80mm;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><?php echo e($company->name ?? 'SellerMind'); ?></div>
        <div class="company-info">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->address ?? null): ?>
                <?php echo e($company->address); ?><br>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->phone ?? null): ?>
                Тел: <?php echo e($company->phone); ?><br>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->inn ?? null): ?>
                ИНН: <?php echo e($company->inn); ?>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="receipt-info">
        <p><strong>Чек #<?php echo e($sale->sale_number); ?></strong></p>
        <p>Дата: <?php echo e($sale->created_at->format('d.m.Y H:i')); ?></p>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty): ?>
            <p>Покупатель: <?php echo e($sale->counterparty->name); ?></p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <p>Кассир: <?php echo e($sale->createdBy->name ?? 'Система'); ?></p>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Наименование</th>
                <th class="qty">Кол</th>
                <th class="price">Цена</th>
                <th class="total">Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $sale->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!($item->metadata['is_expense'] ?? false)): ?>
                <tr>
                    <td><?php echo e(Str::limit($item->product_name, 25)); ?></td>
                    <td class="qty"><?php echo e((int)$item->quantity); ?></td>
                    <td class="price"><?php echo e(number_format($item->unit_price, 0, '.', ' ')); ?></td>
                    <td class="total"><?php echo e(number_format($item->total, 0, '.', ' ')); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Подытог:</span>
            <span><?php echo e(number_format($sale->subtotal, 0, '.', ' ')); ?> <?php echo e($sale->currency); ?></span>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->discount_amount > 0): ?>
        <div class="totals-row">
            <span>Скидка:</span>
            <span>-<?php echo e(number_format($sale->discount_amount, 0, '.', ' ')); ?> <?php echo e($sale->currency); ?></span>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->tax_amount > 0): ?>
        <div class="totals-row">
            <span>НДС:</span>
            <span><?php echo e(number_format($sale->tax_amount, 0, '.', ' ')); ?> <?php echo e($sale->currency); ?></span>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="totals-row grand-total">
            <span>ИТОГО:</span>
            <span><?php echo e(number_format($sale->total_amount, 0, '.', ' ')); ?> <?php echo e($sale->currency); ?></span>
        </div>
    </div>

    <div class="footer">
        <div class="barcode">*<?php echo e($sale->sale_number); ?>*</div>
        <p>Спасибо за покупку!</p>
        <p><?php echo e(now()->format('d.m.Y H:i:s')); ?></p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\sales\print\receipt.blade.php ENDPATH**/ ?>