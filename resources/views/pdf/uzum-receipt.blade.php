<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            width: 100%;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 6px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header .shop-name {
            font-size: 12px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .items td {
            padding: 2px 0;
            vertical-align: top;
        }
        .items .item-name {
            font-size: 10px;
        }
        .items .item-details {
            font-size: 10px;
            color: #555;
        }
        .total-row td {
            padding-top: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .info-row {
            font-size: 10px;
            margin: 2px 0;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="shop-name">{{ $shopName }}</div>
        <h1>ЧЕК</h1>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Заказ:</td>
            <td class="right bold">#{{ $order->external_order_id }}</td>
        </tr>
        <tr>
            <td>Дата:</td>
            <td class="right">{{ $date }}</td>
        </tr>
        <tr>
            <td>Тип:</td>
            <td class="right">{{ $deliveryType }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="items">
        @foreach($items as $i => $item)
        <tr>
            <td colspan="2">
                <div class="item-name">{{ $i + 1 }}. {{ $item->name ?? $item->raw_payload['skuTitle'] ?? 'Товар' }}</div>
                @if(!empty($item->raw_payload['barcode']))
                <div class="item-details">Баркод: {{ $item->raw_payload['barcode'] }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="item-details">
                {{ (int)($item->quantity ?? $item->raw_payload['amount'] ?? 1) }} x {{ number_format((float)($item->price ?? $item->raw_payload['sellerPrice'] ?? 0), 0, '.', ' ') }} сум
            </td>
            <td class="right bold">
                {{ number_format((float)($item->total_price ?? (($item->price ?? 0) * ($item->quantity ?? 1))), 0, '.', ' ') }} сум
            </td>
        </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Позиций:</td>
            <td class="right">{{ $items->count() }}</td>
        </tr>
        <tr class="total-row">
            <td>ИТОГО:</td>
            <td class="right">{{ number_format((float)$order->total_amount, 0, '.', ' ') }} сум</td>
        </tr>
    </table>

    <div class="divider"></div>

    @if($customerName || $customerPhone || $deliveryAddress)
    <div class="info-row">
        @if($customerName)
        <div>Покупатель: {{ $customerName }}</div>
        @endif
        @if($customerPhone)
        <div>Тел.: {{ $customerPhone }}</div>
        @endif
        @if($deliveryAddress)
        <div>Адрес: {{ $deliveryAddress }}</div>
        @endif
    </div>
    <div class="divider"></div>
    @endif

    <div class="footer">
        <div>Спасибо за покупку!</div>
        <div style="margin-top: 4px;">{{ $shopName }} / Uzum Market</div>
    </div>
</body>
</html>
