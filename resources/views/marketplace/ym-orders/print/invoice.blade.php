<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счёт-фактура - Заказ #{{ $order->order_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .company-logo { font-size: 24px; font-weight: bold; color: #333; }
        .company-details { text-align: right; font-size: 11px; }
        .marketplace-tag {
            display: inline-block;
            background: #FFCC00;
            color: #1a1a1a;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .document-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; text-transform: uppercase; }
        .document-number { text-align: center; font-size: 14px; margin-bottom: 20px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .party { width: 48%; }
        .party-title { font-weight: bold; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; color: #666; }
        .party-name { font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .party-details { font-size: 11px; color: #333; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f5f5f5; border: 1px solid #333; padding: 8px; text-align: center; font-size: 11px; font-weight: bold; }
        .items-table td { border: 1px solid #333; padding: 8px; font-size: 11px; }
        .items-table .num { text-align: center; width: 30px; }
        .items-table .name { text-align: left; }
        .items-table .qty { text-align: center; width: 50px; }
        .items-table .unit { text-align: center; width: 40px; }
        .items-table .price { text-align: right; width: 80px; }
        .items-table .sum { text-align: right; width: 100px; }
        .totals-section { display: flex; justify-content: flex-end; margin-bottom: 30px; }
        .totals-table { width: 300px; }
        .totals-table td { padding: 5px 10px; font-size: 12px; }
        .totals-table .label { text-align: left; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .totals-table .grand-total { font-size: 14px; border-top: 2px solid #333; }
        .amount-words { margin-bottom: 30px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; font-style: italic; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-block { width: 45%; }
        .signature-title { font-weight: bold; margin-bottom: 30px; }
        .signature-line { border-bottom: 1px solid #333; margin-bottom: 5px; height: 30px; }
        .signature-name { font-size: 10px; color: #666; }
        .stamp-area { text-align: center; margin-top: 20px; font-size: 10px; color: #999; }
        @media print {
            body { padding: 10mm; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-logo">{{ $company->name ?? 'SellerMind' }}</div>
            <div class="marketplace-tag">Yandex Market</div>
        </div>
        <div class="company-details">
            @if($company->address ?? null)
                {{ $company->address }}<br>
            @endif
            @if($company->phone ?? null)
                Тел: {{ $company->phone }}<br>
            @endif
            @if($company->inn ?? null)
                ИНН: {{ $company->inn }}<br>
            @endif
            @if($company->bank_account ?? null)
                Р/с: {{ $company->bank_account }}
            @endif
        </div>
    </div>

    <div class="document-title">Счёт-фактура</div>
    <div class="document-number">
        № YM-{{ $order->order_id }} от {{ $order->created_at_ym?->format('d.m.Y') ?? $order->created_at->format('d.m.Y') }}
    </div>

    <div class="parties">
        <div class="party">
            <div class="party-title">Поставщик:</div>
            <div class="party-name">{{ $company->name ?? 'SellerMind' }}</div>
            <div class="party-details">
                @if($company->address ?? null)
                    Адрес: {{ $company->address }}<br>
                @endif
                @if($company->inn ?? null)
                    ИНН: {{ $company->inn }}<br>
                @endif
                @if($company->bank_name ?? null)
                    Банк: {{ $company->bank_name }}
                @endif
            </div>
        </div>
        <div class="party">
            <div class="party-title">Покупатель:</div>
            @if($order->customer_name)
                <div class="party-name">{{ $order->customer_name }}</div>
                <div class="party-details">
                    @if($order->customer_phone)
                        Тел: {{ $order->customer_phone }}<br>
                    @endif
                    Доставка: {{ $order->delivery_type ?? '—' }}<br>
                    Служба: {{ $order->delivery_service ?? '—' }}
                </div>
            @else
                <div class="party-name">Покупатель Yandex Market</div>
            @endif
        </div>
    </div>

    @php
        $items = $order->order_data['items'] ?? [];
        $num = 1;
        $totalQty = 0;
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="name">Наименование товара</th>
                <th>Артикул</th>
                <th class="qty">Кол-во</th>
                <th class="unit">Ед.</th>
                <th class="price">Цена</th>
                <th class="sum">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $count = (int)($item['count'] ?? 1);
                    $price = (float)($item['buyerPrice'] ?? $item['price'] ?? 0);
                    $totalQty += $count;
                @endphp
                <tr>
                    <td class="num">{{ $num++ }}</td>
                    <td class="name">{{ $item['offerName'] ?? $item['offerId'] ?? 'Товар' }}</td>
                    <td style="text-align: center;">{{ $item['offerId'] ?? $item['shopSku'] ?? '—' }}</td>
                    <td class="qty">{{ $count }}</td>
                    <td class="unit">шт</td>
                    <td class="price">{{ number_format($price, 2, '.', ' ') }}</td>
                    <td class="sum">{{ number_format($price * $count, 2, '.', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr class="grand-total">
                <td class="label"><strong>Всего к оплате:</strong></td>
                <td class="value">{{ number_format((float)$order->total_price, 2, '.', ' ') }} {{ $order->currency ?? 'RUR' }}</td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        <strong>Всего наименований {{ $totalQty }}, на сумму {{ number_format((float)$order->total_price, 2, '.', ' ') }} {{ $order->currency ?? 'RUR' }}</strong>
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

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
