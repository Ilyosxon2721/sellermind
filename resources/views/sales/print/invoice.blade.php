<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счёт-фактура #{{ $sale->sale_number }}</title>
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
        <div class="company-logo">{{ $company->name ?? 'SellerMind' }}</div>
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
        № {{ $sale->sale_number }} от {{ $sale->created_at->format('d.m.Y') }}
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
                    Банк: {{ $company->bank_name }}<br>
                @endif
            </div>
        </div>
        <div class="party">
            <div class="party-title">Покупатель:</div>
            @if($sale->counterparty)
                <div class="party-name">{{ $sale->counterparty->name }}</div>
                <div class="party-details">
                    @if($sale->counterparty->address)
                        Адрес: {{ $sale->counterparty->address }}<br>
                    @endif
                    @if($sale->counterparty->inn)
                        ИНН: {{ $sale->counterparty->inn }}<br>
                    @endif
                    @if($sale->counterparty->phone)
                        Тел: {{ $sale->counterparty->phone }}
                    @endif
                </div>
            @else
                <div class="party-name">Розничный покупатель</div>
            @endif
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
            @php $num = 1; @endphp
            @foreach($sale->items as $item)
                @if(!($item->metadata['is_expense'] ?? false))
                <tr>
                    <td class="num">{{ $num++ }}</td>
                    <td class="name">
                        {{ $item->product_name }}
                        @if($item->productVariant?->sku)
                            <br><small style="color: #666;">Артикул: {{ $item->productVariant->sku }}</small>
                        @endif
                    </td>
                    <td class="qty">{{ (int)$item->quantity }}</td>
                    <td class="unit">шт</td>
                    <td class="price">{{ number_format($item->unit_price, 2, '.', ' ') }}</td>
                    <td class="sum">{{ number_format($item->total, 2, '.', ' ') }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Подытог:</td>
                <td class="value">{{ number_format($sale->subtotal, 2, '.', ' ') }} {{ $sale->currency }}</td>
            </tr>
            @if($sale->discount_amount > 0)
            <tr>
                <td class="label">Скидка:</td>
                <td class="value">-{{ number_format($sale->discount_amount, 2, '.', ' ') }} {{ $sale->currency }}</td>
            </tr>
            @endif
            @if($sale->tax_amount > 0)
            <tr>
                <td class="label">НДС ({{ $sale->items->first()?->tax_percent ?? 0 }}%):</td>
                <td class="value">{{ number_format($sale->tax_amount, 2, '.', ' ') }} {{ $sale->currency }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label"><strong>Всего к оплате:</strong></td>
                <td class="value">{{ number_format($sale->total_amount, 2, '.', ' ') }} {{ $sale->currency }}</td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        <strong>Всего наименований {{ $sale->items->count() }}, на сумму {{ number_format($sale->total_amount, 2, '.', ' ') }} {{ $sale->currency }}</strong>
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
