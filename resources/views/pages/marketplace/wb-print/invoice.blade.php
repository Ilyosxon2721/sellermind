<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['document_number'] }} | Счёт-фактура</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 20mm;
            max-width: 210mm;
            margin: 0 auto;
            color: #000;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #333;
        }
        .company-logo {
            font-size: 22px;
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
            margin: 20px 0 5px;
            text-transform: uppercase;
        }
        .document-number {
            text-align: center;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* Банковские реквизиты */
        .bank-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .bank-details td {
            border: 1px solid #333;
            padding: 4px 8px;
        }
        .bank-details .label {
            background: #f5f5f5;
            width: 120px;
            font-weight: bold;
        }

        /* Стороны */
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

        /* Таблица товаров */
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
            font-size: 10px;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #333;
            padding: 5px 8px;
            font-size: 11px;
        }
        .items-table .num { text-align: center; width: 25px; }
        .items-table .name { text-align: left; }
        .items-table .sku { text-align: center; width: 80px; font-size: 10px; }
        .items-table .qty { text-align: center; width: 40px; }
        .items-table .unit { text-align: center; width: 30px; }
        .items-table .price { text-align: right; width: 75px; }
        .items-table .sum { text-align: right; width: 85px; }
        .items-table .tax { text-align: center; width: 50px; font-size: 10px; }
        .items-table .tax-sum { text-align: right; width: 70px; font-size: 10px; }
        .items-table tfoot td {
            font-weight: bold;
            background: #f9f9f9;
        }

        /* Итого */
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        .totals-table { width: 300px; }
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

        /* Подписи */
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

        .footer-note {
            margin-top: 20px;
            font-size: 9px;
            color: #999;
            text-align: center;
        }

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
            <span class="wb-label">Wildberries</span>
        </div>
        <div class="company-details">
            @if($data['company']['address']){{ $data['company']['address'] }}<br>@endif
            @if($data['company']['phone'])Тел: {{ $data['company']['phone'] }}<br>@endif
            @if($data['company']['inn'])ИНН: {{ $data['company']['inn'] }}@endif
            @if($data['company']['kpp']) / КПП: {{ $data['company']['kpp'] }}@endif
        </div>
    </div>

    {{-- Заголовок --}}
    <div class="document-title">Счёт-фактура</div>
    <div class="document-number">
        {{ $data['document_number'] }} от {{ $data['document_date']->format('d.m.Y') }}
    </div>

    {{-- Банковские реквизиты (если заполнены) --}}
    @if($data['company']['bank_name'] || $data['company']['bank_account'])
        <table class="bank-details">
            @if($data['company']['bank_name'])
                <tr>
                    <td class="label">Банк:</td>
                    <td>{{ $data['company']['bank_name'] }}</td>
                    @if($data['company']['bank_bik'])
                        <td class="label" style="width: 50px;">БИК:</td>
                        <td style="width: 120px;">{{ $data['company']['bank_bik'] }}</td>
                    @endif
                </tr>
            @endif
            @if($data['company']['bank_account'])
                <tr>
                    <td class="label">Р/счёт:</td>
                    <td>{{ $data['company']['bank_account'] }}</td>
                    @if($data['company']['bank_corr'])
                        <td class="label" style="width: 50px;">Корр:</td>
                        <td style="width: 120px;">{{ $data['company']['bank_corr'] }}</td>
                    @endif
                </tr>
            @endif
        </table>
    @endif

    {{-- Стороны --}}
    <div class="parties">
        <div class="party">
            <div class="party-title">Поставщик:</div>
            <div class="party-name">{{ $data['company']['name'] }}</div>
            <div class="party-details">
                @if($data['company']['address'])Адрес: {{ $data['company']['address'] }}<br>@endif
                @if($data['company']['inn'])ИНН: {{ $data['company']['inn'] }}@endif
                @if($data['company']['kpp']) КПП: {{ $data['company']['kpp'] }}@endif
                @if($data['company']['ogrn'])<br>ОГРН: {{ $data['company']['ogrn'] }}@endif
            </div>
        </div>
        <div class="party">
            <div class="party-title">Покупатель:</div>
            <div class="party-name">{{ $data['buyer']['name'] }}</div>
            <div class="party-details">
                @if($data['buyer']['phone'])Тел: {{ $data['buyer']['phone'] }}<br>@endif
                Заказ WB: #{{ $data['order']['external_order_id'] }}<br>
                Дата заказа: {{ $data['order']['ordered_at']?->format('d.m.Y H:i') ?? '-' }}
            </div>
        </div>
    </div>

    {{-- Таблица товаров --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="num">#</th>
                <th class="name">Наименование товара</th>
                <th class="sku">Артикул</th>
                <th class="qty">Кол.</th>
                <th class="unit">Ед.</th>
                <th class="price">Цена без НДС</th>
                <th class="tax">НДС</th>
                <th class="tax-sum">Сумма НДС</th>
                <th class="sum">Сумма с НДС</th>
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
                    <td class="price">{{ number_format($item['total'], 2, '.', ' ') }}</td>
                    <td class="tax">Без НДС</td>
                    <td class="tax-sum">-</td>
                    <td class="sum">{{ number_format($item['total'], 2, '.', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;"><strong>Итого:</strong></td>
                <td class="price">{{ number_format($data['totals']['subtotal'], 2, '.', ' ') }}</td>
                <td class="tax">-</td>
                <td class="tax-sum">-</td>
                <td class="sum">{{ number_format($data['totals']['total'], 2, '.', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Итого --}}
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">Сумма без НДС:</td>
                <td class="value">{{ number_format($data['totals']['total'], 2, '.', ' ') }} {{ $data['totals']['currency'] }}</td>
            </tr>
            <tr>
                <td class="label">НДС:</td>
                <td class="value">Без НДС</td>
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
            <div class="signature-title">Руководитель организации:</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО</div>
            <div class="stamp">М.П.</div>
        </div>
        <div class="signature-block">
            <div class="signature-title">Главный бухгалтер:</div>
            <div class="signature-line"></div>
            <div class="signature-hint">подпись / ФИО</div>
        </div>
    </div>

    <div class="footer-note">
        Документ сформирован в SellerMind | {{ now()->format('d.m.Y H:i') }}
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
