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
            margin-bottom: 20px;
            border-bottom: 3px double #3A007D;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            color: #3A007D;
            letter-spacing: 2px;
        }
        .header .doc-number {
            font-size: 14px;
            margin-top: 5px;
        }
        .header .doc-date {
            font-size: 12px;
            color: #555;
            margin-top: 3px;
        }
        .parties {
            width: 100%;
            margin-bottom: 20px;
        }
        .parties td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .party-block {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 4px;
        }
        .party-block h3 {
            font-size: 11px;
            font-weight: bold;
            color: #3A007D;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }
        .party-row {
            font-size: 11px;
            margin-bottom: 3px;
        }
        .party-label {
            color: #888;
            font-size: 10px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.items th {
            background: #3A007D;
            color: #fff;
            padding: 8px 5px;
            font-size: 10px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #3A007D;
        }
        table.items td {
            padding: 6px 5px;
            border: 1px solid #ccc;
            font-size: 11px;
            vertical-align: top;
        }
        .col-num { width: 30px; text-align: center; }
        .col-name { }
        .col-unit { width: 45px; text-align: center; }
        .col-qty { width: 45px; text-align: center; }
        .col-price { width: 85px; text-align: right; }
        .col-sum { width: 95px; text-align: right; }
        .total-row td {
            font-weight: bold;
            font-size: 12px;
            background: #f5f0ff;
        }
        .summary {
            margin-bottom: 25px;
        }
        .summary-row {
            font-size: 12px;
            margin-bottom: 4px;
        }
        .summary-total {
            font-size: 14px;
            font-weight: bold;
            color: #3A007D;
            margin-top: 8px;
            padding: 8px;
            background: #f5f0ff;
            border: 1px solid #3A007D;
            border-radius: 3px;
        }
        .note {
            font-size: 10px;
            color: #888;
            font-style: italic;
            margin-bottom: 20px;
        }
        .signatures {
            margin-top: 35px;
        }
        .signatures table {
            width: 100%;
        }
        .signatures td {
            width: 50%;
            padding-top: 35px;
            vertical-align: bottom;
        }
        .sig-block {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 11px;
            width: 85%;
        }
        .sig-label {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }
        .stamp-area {
            width: 70px;
            height: 70px;
            border: 1px dashed #ccc;
            border-radius: 50%;
            display: inline-block;
            margin-top: 10px;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>СЧЁТ-ФАКТУРА</h1>
        <div class="doc-number">{{ $invoiceNumber }}</div>
        <div class="doc-date">от {{ $date }}</div>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="party-block">
                    <h3>Продавец</h3>
                    <div class="party-row"><strong>{{ $shopName }}</strong></div>
                    <div class="party-row">
                        <span class="party-label">Маркетплейс:</span> Uzum Market
                    </div>
                    <div class="party-row">
                        <span class="party-label">Тип доставки:</span> {{ $deliveryType }}
                    </div>
                </div>
            </td>
            <td>
                <div class="party-block">
                    <h3>Покупатель</h3>
                    <div class="party-row"><strong>{{ $customerName ?? '---' }}</strong></div>
                    @if($customerPhone)
                    <div class="party-row">
                        <span class="party-label">Тел.:</span> {{ $customerPhone }}
                    </div>
                    @endif
                    @if($deliveryAddress)
                    <div class="party-row">
                        <span class="party-label">Адрес:</span> {{ $deliveryAddress }}
                    </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="col-num">№</th>
                <th class="col-name">Наименование товара</th>
                <th class="col-unit">Ед.</th>
                <th class="col-qty">Кол-во</th>
                <th class="col-price">Цена (сум)</th>
                <th class="col-sum">Сумма (сум)</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; $totalQty = 0; @endphp
            @foreach($items as $i => $item)
            @php
                $qty = (int)($item->quantity ?? $item->raw_payload['amount'] ?? 1);
                $price = (float)($item->price ?? $item->raw_payload['sellerPrice'] ?? 0);
                $lineTotal = (float)($item->total_price ?? ($price * $qty));
                $grandTotal += $lineTotal;
                $totalQty += $qty;
            @endphp
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-name">
                    {{ $item->name ?? $item->raw_payload['skuTitle'] ?? 'Товар' }}
                    @if(!empty($item->raw_payload['barcode']))
                    <br><small style="color: #888;">{{ $item->raw_payload['barcode'] }}</small>
                    @endif
                </td>
                <td class="col-unit">шт.</td>
                <td class="col-qty">{{ $qty }}</td>
                <td class="col-price">{{ number_format($price, 0, '.', ' ') }}</td>
                <td class="col-sum">{{ number_format($lineTotal, 0, '.', ' ') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Итого:</td>
                <td class="col-qty">{{ $totalQty }}</td>
                <td class="col-price"></td>
                <td class="col-sum">{{ number_format((float)$order->total_amount ?: $grandTotal, 0, '.', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div class="note">Без НДС (в соответствии с режимом налогообложения продавца)</div>
        <div class="summary-total">
            Всего к оплате: {{ number_format((float)$order->total_amount ?: $grandTotal, 0, '.', ' ') }} сум
        </div>
    </div>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sig-block">Руководитель</div>
                    <div class="sig-label">подпись / ФИО</div>
                </td>
                <td>
                    <div class="sig-block">Бухгалтер</div>
                    <div class="sig-label">подпись / ФИО</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Заказ #{{ $order->external_order_id }} | Документ сформирован в системе SellerMind | {{ $date }}
    </div>
</body>
</html>
