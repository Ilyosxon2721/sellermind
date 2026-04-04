<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статус заказа</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #2563eb; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .header p { margin: 4px 0 0; opacity: 0.85; font-size: 14px; }
        .content { padding: 32px; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .status-new { background: #dbeafe; color: #1d4ed8; }
        .status-confirmed { background: #d1fae5; color: #059669; }
        .status-processing { background: #fef3c7; color: #d97706; }
        .status-shipped { background: #e0e7ff; color: #4f46e5; }
        .status-delivered { background: #d1fae5; color: #047857; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .info-label { color: #6b7280; }
        .info-value { font-weight: 500; color: #111827; }
        .items-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px; }
        .items-table th { text-align: left; padding: 8px; background: #f9fafb; color: #6b7280; font-weight: 500; border-bottom: 1px solid #e5e7eb; }
        .items-table td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
        .total-row { font-weight: 600; font-size: 16px; }
        .footer { padding: 24px 32px; background: #f9fafb; text-align: center; font-size: 13px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $storeName }}</h1>
            <p>Обновление по заказу {{ $order->order_number }}</p>
        </div>

        <div class="content">
            <p style="margin: 0 0 16px; font-size: 16px;">
                Здравствуйте, <strong>{{ $order->customer_name }}</strong>!
            </p>

            <p style="margin: 0 0 20px; color: #374151;">
                Статус вашего заказа изменён:
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <span class="status-badge status-{{ $order->status }}">
                    {{ $statusLabel }}
                </span>
            </div>

            @if($order->status === 'shipped')
                <p style="color: #374151; background: #f0fdf4; padding: 12px 16px; border-radius: 8px; border-left: 3px solid #22c55e;">
                    Ваш заказ отправлен! Ожидайте доставку.
                </p>
            @elseif($order->status === 'delivered')
                <p style="color: #374151; background: #f0fdf4; padding: 12px 16px; border-radius: 8px; border-left: 3px solid #22c55e;">
                    Заказ доставлен. Спасибо за покупку!
                </p>
            @elseif($order->status === 'cancelled')
                <p style="color: #374151; background: #fef2f2; padding: 12px 16px; border-radius: 8px; border-left: 3px solid #ef4444;">
                    Заказ отменён. Если у вас есть вопросы, свяжитесь с нами.
                </p>
            @endif

            <h3 style="margin: 24px 0 12px; font-size: 15px; color: #374151;">Детали заказа</h3>

            <div class="info-row">
                <span class="info-label">Номер заказа</span>
                <span class="info-value">{{ $order->order_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Дата</span>
                <span class="info-value">{{ $order->created_at?->format('d.m.Y H:i') }}</span>
            </div>

            @if($order->items && $order->items->isNotEmpty())
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th style="text-align: right;">Кол-во</th>
                            <th style="text-align: right;">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td style="text-align: right;">{{ $item->quantity }}</td>
                                <td style="text-align: right;">{{ number_format((float)$item->total, 0, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if((float)$order->discount > 0)
                <div class="info-row">
                    <span class="info-label">Скидка</span>
                    <span class="info-value" style="color: #059669;">-{{ number_format((float)$order->discount, 0, '.', ' ') }}</span>
                </div>
            @endif

            @if((float)$order->delivery_price > 0)
                <div class="info-row">
                    <span class="info-label">Доставка</span>
                    <span class="info-value">{{ number_format((float)$order->delivery_price, 0, '.', ' ') }}</span>
                </div>
            @endif

            <div class="info-row total-row" style="border-bottom: none; padding-top: 12px;">
                <span>Итого</span>
                <span>{{ number_format((float)$order->total, 0, '.', ' ') }} UZS</span>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">{{ $storeName }} &mdash; Спасибо за покупку!</p>
        </div>
    </div>
</body>
</html>
