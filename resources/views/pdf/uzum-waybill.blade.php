<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #3A007D;
            margin-bottom: 5px;
        }
        .header .doc-number {
            font-size: 13px;
            color: #555;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-block h3 {
            font-size: 12px;
            font-weight: bold;
            color: #3A007D;
            border-bottom: 1px solid #3A007D;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }
        .info-row {
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 130px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #3A007D;
            color: #fff;
            padding: 8px 6px;
            font-size: 11px;
            text-align: left;
            font-weight: bold;
        }
        table.items td {
            padding: 7px 6px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
            vertical-align: top;
        }
        table.items tr:nth-child(even) td {
            background: #f9f9f9;
        }
        table.items .num { width: 30px; text-align: center; }
        table.items .qty { width: 50px; text-align: center; }
        table.items .price { width: 90px; text-align: right; }
        table.items .total { width: 100px; text-align: right; }
        .total-row td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #3A007D;
            padding-top: 10px;
        }
        .signatures {
            margin-top: 40px;
        }
        .signatures table {
            width: 100%;
        }
        .signatures td {
            width: 50%;
            padding-top: 40px;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 11px;
            width: 80%;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ТОВАРНАЯ НАКЛАДНАЯ</h1>
        <div class="doc-number">
            № ТН-{{ $order->external_order_id }} от {{ $date }}
        </div>
    </div>

    <div class="info-block">
        <h3>Отправитель</h3>
        <div class="info-row">
            <span class="info-label">Магазин:</span> {{ $shopName }}
        </div>
        <div class="info-row">
            <span class="info-label">Маркетплейс:</span> Uzum Market
        </div>
        <div class="info-row">
            <span class="info-label">Тип доставки:</span> {{ strtoupper($order->delivery_type ?? 'FBS') }}
        </div>
    </div>

    <div class="info-block">
        <h3>Получатель</h3>
        <div class="info-row">
            <span class="info-label">ФИО:</span> {{ $order->customer_name ?? '---' }}
        </div>
        @if($order->customer_phone)
        <div class="info-row">
            <span class="info-label">Телефон:</span> {{ $order->customer_phone }}
        </div>
        @endif
        @if($order->delivery_address_full)
        <div class="info-row">
            <span class="info-label">Адрес доставки:</span> {{ $order->delivery_address_full }}
        </div>
        @elseif($order->delivery_city)
        <div class="info-row">
            <span class="info-label">Город:</span> {{ $order->delivery_city }}
        </div>
        @endif
    </div>

    <div class="info-block">
        <h3>Заказ #{{ $order->external_order_id }}</h3>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="num">№</th>
                <th>Наименование</th>
                <th class="qty">Кол-во</th>
                <th class="price">Цена (сум)</th>
                <th class="total">Сумма (сум)</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($items as $i => $item)
            @php
                $qty = (int)($item->quantity ?? $item->raw_payload['amount'] ?? 1);
                $price = (float)($item->price ?? $item->raw_payload['sellerPrice'] ?? 0);
                $lineTotal = (float)($item->total_price ?? ($price * $qty));
                $grandTotal += $lineTotal;
            @endphp
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>
                    {{ $item->name ?? $item->raw_payload['skuTitle'] ?? 'Товар' }}
                    @if(!empty($item->raw_payload['barcode']))
                    <br><small style="color: #888;">Баркод: {{ $item->raw_payload['barcode'] }}</small>
                    @endif
                </td>
                <td class="qty">{{ $qty }}</td>
                <td class="price">{{ number_format($price, 0, '.', ' ') }}</td>
                <td class="total">{{ number_format($lineTotal, 0, '.', ' ') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3"></td>
                <td class="price" style="text-align: right;">Итого:</td>
                <td class="total">{{ number_format((float)$order->total_amount ?: $grandTotal, 0, '.', ' ') }} сум</td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sig-line">Отправитель: _____________________</div>
                </td>
                <td>
                    <div class="sig-line">Получатель: _____________________</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Документ сформирован автоматически в системе SellerMind / {{ $date }}
    </div>
</body>
</html>
