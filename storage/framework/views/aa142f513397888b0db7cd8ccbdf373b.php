<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Накладная #<?php echo e($sale->sale_number); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 15mm;
            max-width: 210mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .document-number {
            font-size: 14px;
            margin-bottom: 15px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .info-block {
            width: 48%;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            width: 100px;
            font-weight: bold;
            color: #666;
        }
        .info-value {
            flex: 1;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #e0e0e0;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            font-size: 11px;
        }
        .items-table .num {
            text-align: center;
            width: 30px;
        }
        .items-table .sku {
            text-align: center;
            width: 80px;
        }
        .items-table .barcode {
            text-align: center;
            width: 100px;
        }
        .items-table .name {
            text-align: left;
        }
        .items-table .qty {
            text-align: center;
            width: 50px;
        }
        .items-table .unit {
            text-align: center;
            width: 40px;
        }
        .items-table .price {
            text-align: right;
            width: 80px;
        }
        .items-table .sum {
            text-align: right;
            width: 90px;
        }
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-box {
            border: 2px solid #333;
            padding: 15px;
            width: 250px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .totals-row.total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .summary {
            margin-bottom: 30px;
            padding: 10px;
            border: 1px solid #ddd;
            background: #fafafa;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-block {
            width: 30%;
            text-align: center;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 25px 0 5px 0;
        }
        .signature-hint {
            font-size: 9px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body {
                padding: 10mm;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="document-title">Товарная накладная</div>
        <div class="document-number">№ <?php echo e($sale->sale_number); ?> от <?php echo e($sale->created_at->format('d.m.Y')); ?></div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <div class="info-row">
                <span class="info-label">Поставщик:</span>
                <span class="info-value"><?php echo e($company->name ?? 'SellerMind'); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->inn ?? null): ?>
            <div class="info-row">
                <span class="info-label">ИНН:</span>
                <span class="info-value"><?php echo e($company->inn); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->address ?? null): ?>
            <div class="info-row">
                <span class="info-label">Адрес:</span>
                <span class="info-value"><?php echo e($company->address); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div class="info-block">
            <div class="info-row">
                <span class="info-label">Получатель:</span>
                <span class="info-value"><?php echo e($sale->counterparty?->name ?? 'Розничный покупатель'); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty?->inn): ?>
            <div class="info-row">
                <span class="info-label">ИНН:</span>
                <span class="info-value"><?php echo e($sale->counterparty->inn); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty?->address): ?>
            <div class="info-row">
                <span class="info-label">Адрес:</span>
                <span class="info-value"><?php echo e($sale->counterparty->address); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="info-section" style="background: #fff;">
        <div class="info-block">
            <div class="info-row">
                <span class="info-label">Склад:</span>
                <span class="info-value"><?php echo e($sale->warehouse?->name ?? 'Основной склад'); ?></span>
            </div>
        </div>
        <div class="info-block">
            <div class="info-row">
                <span class="info-label">Основание:</span>
                <span class="info-value">Продажа товаров</span>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="num">№</th>
                <th class="sku">Артикул</th>
                <th class="barcode">Штрихкод</th>
                <th class="name">Наименование</th>
                <th class="qty">Кол-во</th>
                <th class="unit">Ед.</th>
                <th class="price">Цена</th>
                <th class="sum">Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php $num = 1; $totalQty = 0; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $sale->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!($item->metadata['is_expense'] ?? false)): ?>
                <?php $totalQty += $item->quantity; ?>
                <tr>
                    <td class="num"><?php echo e($num++); ?></td>
                    <td class="sku"><?php echo e($item->productVariant?->sku ?? '-'); ?></td>
                    <td class="barcode"><?php echo e($item->productVariant?->barcode ?? '-'); ?></td>
                    <td class="name"><?php echo e($item->product_name); ?></td>
                    <td class="qty"><?php echo e((int)$item->quantity); ?></td>
                    <td class="unit">шт</td>
                    <td class="price"><?php echo e(number_format($item->unit_price, 2, '.', ' ')); ?></td>
                    <td class="sum"><?php echo e(number_format($item->total, 2, '.', ' ')); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Итого:</td>
                <td class="qty" style="font-weight: bold;"><?php echo e((int)$totalQty); ?></td>
                <td></td>
                <td></td>
                <td class="sum" style="font-weight: bold;"><?php echo e(number_format($sale->total_amount, 2, '.', ' ')); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <strong>Всего отпущено <?php echo e((int)$totalQty); ?> наименований на сумму <?php echo e(number_format($sale->total_amount, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></strong>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-title">Отпустил</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО</div>
        </div>
        <div class="signature-block">
            <div class="signature-title">Принял</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО</div>
        </div>
        <div class="signature-block">
            <div class="signature-title">М.П.</div>
            <div style="height: 50px; border: 1px dashed #ccc; margin-top: 10px;"></div>
        </div>
    </div>

    <div class="footer">
        Документ сформирован в системе SellerMind <?php echo e(now()->format('d.m.Y H:i:s')); ?>

    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\sales\print\waybill.blade.php ENDPATH**/ ?>