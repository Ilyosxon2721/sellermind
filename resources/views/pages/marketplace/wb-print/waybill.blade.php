<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['document_number'] }} | Товарная накладная</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 15mm;
            max-width: 210mm;
            margin: 0 auto;
            color: #000;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .company-logo {
            font-size: 20px;
            font-weight: bold;
        }
        .company-details {
            text-align: right;
            font-size: 11px;
        }
        .wb-label {
            display: inline-block;
            background: #CB11AB;
            color: #fff;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
        }
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 5px;
            text-transform: uppercase;
        }
        .document-number {
            text-align: center;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .party { width: 48%; }
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
            margin-bottom: 3px;
        }
        .party-details { font-size: 11px; color: #333; }
        .order-meta {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .order-meta span { margin-right: 20px; }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background: #f0f0f0;
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            font-size: 11px;
        }
        .items-table .num { text-align: center; width: 30px; }
        .items-table .name { text-align: left; }
        .items-table .sku { text-align: center; width: 100px; font-size: 10px; }
        .items-table .qty { text-align: center; width: 50px; }
        .items-table .unit { text-align: center; width: 35px; }
        .items-table .price { text-align: right; width: 80px; }
        .items-table .sum { text-align: right; width: 90px; font-weight: bold; }
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .totals-table { width: 280px; }
        .totals-table td { padding: 4px 8px; font-size: 12px; }
        .totals-table .label { text-align: left; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .totals-table .grand-total {
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .amount-words {
            margin-bottom: 25px;
            padding: 8px 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-style: italic;
            font-size: 11px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-block { width: 45%; }
        .signature-title { font-weight: bold; margin-bottom: 25px; font-size: 11px; }
        .signature-line { border-bottom: 1px solid #333; height: 25px; margin-bottom: 4px; }
        .signature-hint { font-size: 9px; color: #999; }
        .stamp { text-align: center; margin-top: 15px; font-size: 9px; color: #999; }
        @media print {
            body { padding: 10mm; }
            @page { size: A4; margin: 10mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    {{-- Шапка --}}
    <div class="header">
        <div>
            <div class="company-logo">{{ $data['company']['name'] }}</div>
            <span class="wb-label">Wildberries {{ $data['delivery']['type'] }}</span>
        </div>
        <div class="company-details">
            @if($data['company']['address']){{ $data['company']['address'] }}<br>@endif
            @if($data['company']['phone'])Тел: {{ $data['company']['phone'] }}<br>@endif
            @if($data['company']['inn'])ИНН: {{ $data['company']['inn'] }}@endif
            @if($data['company']['kpp']) / КПП: {{ $data['company']['kpp'] }}@endif
        </div>
    </div>

    {{-- Заголовок --}}
    <div class="document-title">Товарная накладная</div>
    <div class="document-number">
        {{ $data['document_number'] }} от {{ $data['document_date']->format('d.m.Y') }}
    </div>

    {{-- Данные о заказе --}}
    <div class="order-meta">
        <span><strong>Заказ WB:</strong> #{{ $data['order']['external_order_id'] }}</span>
        <span><strong>Дата:</strong> {{ $data['order']['ordered_at']?->format('d.m.Y H:i') ?? '-' }}</span>
        <span><strong>Доставка:</strong> {{ $data['delivery']['type'] }}</span>
        @if($data['delivery']['supply_id'])
            <span><strong>Поставка:</strong> {{ $data['delivery']['supply_id'] }}</span>
        @endif
    </div>

    {{-- Стороны --}}
    <div class="parties">
        <div class="party">
            <div class="party-title">Отправитель (продавец):</div>
            <div class="party-name">{{ $data['company']['name'] }}</div>
            <div class="party-details">
                @if($data['company']['address'])Адрес: {{ $data['company']['address'] }}<br>@endif
                @if($data['company']['inn'])ИНН: {{ $data['company']['inn'] }}<br>@endif
                @if($data['company']['bank_name'])Банк: {{ $data['company']['bank_name'] }}@endif
            </div>
        </div>
        <div class="party">
            <div class="party-title">Получатель (покупатель):</div>
            <div class="party-name">{{ $data['buyer']['name'] }}</div>
            <div class="party-details">
                @if($data['buyer']['phone'])Тел: {{ $data['buyer']['phone'] }}@endif
            </div>
        </div>
    </div>

    {{-- Таблица товаров --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="num">#</th>
                <th class="name">Наименование</th>
                <th class="sku">Артикул / SKU</th>
                <th class="qty">Кол.</th>
                <th class="unit">Ед.</th>
                <th class="price">Цена</th>
                <th class="sum">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['items'] as $i => $item)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="name">{{ $item['name'] }}</td>
                    <td class="sku">{{ $item['sku'] ?? '-' }}</td>
                    <td class="qty">{{ $item['quantity'] }}</td>
                    <td class="unit">шт</td>
                    <td class="price">{{ number_format($item['price'], 2, '.', ' ') }}</td>
                    <td class="sum">{{ number_format($item['total'], 2, '.', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Итого --}}
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">Подытог:</td>
                <td class="value">{{ number_format($data['totals']['subtotal'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label"><strong>Всего к оплате:</strong></td>
                <td class="value">{{ number_format($data['totals']['total'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}</td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        <strong>Всего наименований {{ $data['totals']['items_count'] }}, на сумму {{ number_format($data['totals']['total'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}</strong>
    </div>

    {{-- Подписи --}}
    <div class="signatures">
        <div class="signature-block">
            <div class="signature-title">Отпустил:</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО / дата</div>
            <div class="stamp">М.П.</div>
        </div>
        <div class="signature-block">
            <div class="signature-title">Принял:</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО / дата</div>
            <div class="stamp">М.П.</div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
