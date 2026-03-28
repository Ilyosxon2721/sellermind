<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['document_number'] }} | Чек</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
            padding: 10mm;
            max-width: 80mm;
            margin: 0 auto;
            color: #000;
        }
        .receipt { border: 1px dashed #999; padding: 8mm; }
        .center { text-align: center; }
        .divider {
            border-bottom: 1px dashed #999;
            margin: 6px 0;
        }
        .shop-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .shop-details { font-size: 10px; color: #333; }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0 4px;
        }
        .doc-number { font-size: 11px; margin-bottom: 4px; }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 11px;
        }
        .item-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 8px;
        }
        .item-price { white-space: nowrap; font-weight: bold; }
        .item-details { font-size: 10px; color: #555; margin-left: 8px; }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin: 6px 0;
        }
        .order-info { font-size: 10px; color: #555; margin: 3px 0; }
        .footer { font-size: 9px; color: #777; margin-top: 8px; }
        .wb-badge {
            display: inline-block;
            background: #CB11AB;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        @media print {
            body { padding: 0; }
            @page { size: 80mm auto; margin: 5mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        {{-- Шапка магазина --}}
        <div class="center">
            <div class="shop-name">{{ $data['company']['name'] }}</div>
            <div class="shop-details">
                @if($data['company']['address']){{ $data['company']['address'] }}<br>@endif
                @if($data['company']['phone'])Тел: {{ $data['company']['phone'] }}<br>@endif
                @if($data['company']['inn'])ИНН: {{ $data['company']['inn'] }}@endif
            </div>
        </div>

        <div class="divider"></div>

        {{-- Заголовок документа --}}
        <div class="center">
            <div class="doc-title">КАССОВЫЙ ЧЕК</div>
            <div class="doc-number">{{ $data['document_number'] }}</div>
            <div class="doc-number">{{ $data['document_date']->format('d.m.Y H:i') }}</div>
            <span class="wb-badge">Wildberries</span>
        </div>

        <div class="divider"></div>

        {{-- Информация о заказе --}}
        <div class="order-info">
            Заказ WB: #{{ $data['order']['external_order_id'] }}<br>
            Тип: {{ $data['order']['delivery_type'] }}<br>
            @if($data['order']['article'])Артикул: {{ $data['order']['article'] }}@endif
        </div>

        <div class="divider"></div>

        {{-- Товарные позиции --}}
        @foreach($data['items'] as $i => $item)
            <div class="item-row">
                <span class="item-name">{{ $i + 1 }}. {{ $item['name'] }}</span>
                <span class="item-price">{{ number_format($item['total'], 2, '.', ' ') }}</span>
            </div>
            <div class="item-details">
                {{ $item['quantity'] }} x {{ number_format($item['price'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}
                @if($item['sku']) | {{ $item['sku'] }}@endif
            </div>
        @endforeach

        <div class="divider"></div>

        {{-- Итого --}}
        <div class="total-row">
            <span>ИТОГО:</span>
            <span>{{ number_format($data['totals']['total'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}</span>
        </div>
        <div class="order-info">
            Позиций: {{ $data['totals']['items_count'] }}
        </div>

        <div class="divider"></div>

        {{-- Подвал --}}
        <div class="center footer">
            Документ сформирован в SellerMind<br>
            {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
