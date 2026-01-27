<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счёт-фактура #<?php echo e($sale->sale_number); ?></title>
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
            padding: 20mm;
            max-width: 210mm;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .company-details {
            text-align: right;
            font-size: 11px;
        }
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .document-number {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .party {
            width: 48%;
        }
        .party-title {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
            color: #666;
        }
        .party-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .party-details {
            font-size: 11px;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #f5f5f5;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 11px;
        }
        .items-table .num {
            text-align: center;
            width: 30px;
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
            width: 100px;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 300px;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 12px;
        }
        .totals-table .label {
            text-align: left;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .totals-table .grand-total {
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .amount-words {
            margin-bottom: 30px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-style: italic;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-block {
            width: 45%;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 30px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 30px;
        }
        .signature-name {
            font-size: 10px;
            color: #666;
        }
        .stamp-area {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #999;
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
        <div class="company-logo"><?php echo e($company->name ?? 'SellerMind'); ?></div>
        <div class="company-details">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->address ?? null): ?>
                <?php echo e($company->address); ?><br>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->phone ?? null): ?>
                Тел: <?php echo e($company->phone); ?><br>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->inn ?? null): ?>
                ИНН: <?php echo e($company->inn); ?><br>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->bank_account ?? null): ?>
                Р/с: <?php echo e($company->bank_account); ?>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="document-title">Счёт-фактура</div>
    <div class="document-number">
        № <?php echo e($sale->sale_number); ?> от <?php echo e($sale->created_at->format('d.m.Y')); ?>

    </div>

    <div class="parties">
        <div class="party">
            <div class="party-title">Поставщик:</div>
            <div class="party-name"><?php echo e($company->name ?? 'SellerMind'); ?></div>
            <div class="party-details">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->address ?? null): ?>
                    Адрес: <?php echo e($company->address); ?><br>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->inn ?? null): ?>
                    ИНН: <?php echo e($company->inn); ?><br>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->bank_name ?? null): ?>
                    Банк: <?php echo e($company->bank_name); ?><br>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        <div class="party">
            <div class="party-title">Покупатель:</div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty): ?>
                <div class="party-name"><?php echo e($sale->counterparty->name); ?></div>
                <div class="party-details">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty->address): ?>
                        Адрес: <?php echo e($sale->counterparty->address); ?><br>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty->inn): ?>
                        ИНН: <?php echo e($sale->counterparty->inn); ?><br>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->counterparty->phone): ?>
                        Тел: <?php echo e($sale->counterparty->phone); ?>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php else: ?>
                <div class="party-name">Розничный покупатель</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="num">№</th>
                <th class="name">Наименование товара/услуги</th>
                <th class="qty">Кол-во</th>
                <th class="unit">Ед.</th>
                <th class="price">Цена</th>
                <th class="sum">Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php $num = 1; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $sale->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!($item->metadata['is_expense'] ?? false)): ?>
                <tr>
                    <td class="num"><?php echo e($num++); ?></td>
                    <td class="name">
                        <?php echo e($item->product_name); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->productVariant?->sku): ?>
                            <br><small style="color: #666;">Артикул: <?php echo e($item->productVariant->sku); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="qty"><?php echo e((int)$item->quantity); ?></td>
                    <td class="unit">шт</td>
                    <td class="price"><?php echo e(number_format($item->unit_price, 2, '.', ' ')); ?></td>
                    <td class="sum"><?php echo e(number_format($item->total, 2, '.', ' ')); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Подытог:</td>
                <td class="value"><?php echo e(number_format($sale->subtotal, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></td>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->discount_amount > 0): ?>
            <tr>
                <td class="label">Скидка:</td>
                <td class="value">-<?php echo e(number_format($sale->discount_amount, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></td>
            </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->tax_amount > 0): ?>
            <tr>
                <td class="label">НДС (<?php echo e($sale->items->first()?->tax_percent ?? 0); ?>%):</td>
                <td class="value"><?php echo e(number_format($sale->tax_amount, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></td>
            </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <tr class="grand-total">
                <td class="label"><strong>Всего к оплате:</strong></td>
                <td class="value"><?php echo e(number_format($sale->total_amount, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        <strong>Всего наименований <?php echo e($sale->items->count()); ?>, на сумму <?php echo e(number_format($sale->total_amount, 2, '.', ' ')); ?> <?php echo e($sale->currency); ?></strong>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-title">Поставщик:</div>
            <div class="signature-line"></div>
            <div class="signature-name">подпись / ФИО</div>
            <div class="stamp-area">М.П.</div>
        </div>
        <div class="signature-block">
            <div class="signature-title">Покупатель:</div>
            <div class="signature-line"></div>
            <div class="signature-name">подпись / ФИО</div>
            <div class="stamp-area">М.П.</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\sales\print\invoice.blade.php ENDPATH**/ ?>