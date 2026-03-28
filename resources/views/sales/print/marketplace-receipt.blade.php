<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чек #{{ $order['order_number'] }}</title>
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
        .marketplace-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
            margin-top: 8px;
        }
        .marketplace-uzum { background: #7B2FBE; }
        .marketplace-wb { background: #CB11AB; }
        .marketplace-ozon { background: #005BFF; }
        .marketplace-ym { background: #FFCC00; color: #000; }
        .marketplace-sale { background: #666; }
        .account-name {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
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
        <div class="company-name">{{ $company->name ?? 'SellerMind' }}</div>
        <div class="company-info">
            @if($company->address ?? null)
                {{ $company->address }}<br>
            @endif
            @if($company->phone ?? null)
                Тел: {{ $company->phone }}<br>
            @endif
            @if($company->inn ?? null)
                ИНН: {{ $company->inn }}
            @endif
        </div>
        <div class="marketplace-badge marketplace-{{ $order['marketplace'] }}">
            {{ $order['marketplace_label'] }}
        </div>
        <div class="account-name">{{ $order['account_name'] }}</div>
    </div>

    <div class="receipt-info">
        <p><strong>Чек #{{ $order['order_number'] }}</strong></p>
        <p>Дата: {{ $order['date']->format('d.m.Y H:i') }}</p>
        @if($order['customer_name'] ?? null)
            <p>Покупатель: {{ $order['customer_name'] }}</p>
        @endif
        @if($order['customer_phone'] ?? null)
            <p>Тел: {{ $order['customer_phone'] }}</p>
        @endif
        @if($order['delivery_address'] ?? null)
            <p>Адрес: {{ $order['delivery_address'] }}</p>
        @endif
        @if($order['status'] ?? null)
            <p>Статус: {{ $order['status'] }}</p>
        @endif
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
            @foreach($order['items'] as $item)
            <tr>
                <td>{{ Str::limit($item['name'], 25) }}</td>
                <td class="qty">{{ (int)$item['quantity'] }}</td>
                <td class="price">{{ number_format($item['price'], 0, '.', ' ') }}</td>
                <td class="total">{{ number_format($item['total'], 0, '.', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row grand-total">
            <span>ИТОГО:</span>
            <span>{{ number_format($order['total_amount'], 0, '.', ' ') }} {{ $order['currency'] }}</span>
        </div>
    </div>

    @if($order['notes'] ?? null)
    <div style="margin-top: 10px; font-size: 10px; border-top: 1px dashed #ccc; padding-top: 5px;">
        <strong>Примечание:</strong> {{ $order['notes'] }}
    </div>
    @endif

    <div class="footer">
        <div class="barcode">*{{ $order['order_number'] }}*</div>
        <p>Спасибо за покупку!</p>
        <p>{{ now()->format('d.m.Y H:i:s') }}</p>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
