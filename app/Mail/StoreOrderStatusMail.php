<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Store\StoreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email-уведомление покупателю об изменении статуса заказа
 */
final class StoreOrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const STATUS_LABELS = [
        'new' => 'Новый',
        'confirmed' => 'Подтверждён',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ];

    public function __construct(
        public StoreOrder $order,
        public string $storeName,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = self::STATUS_LABELS[$this->order->status] ?? $this->order->status;

        return new Envelope(
            subject: "{$this->storeName} — Заказ {$this->order->order_number}: {$statusLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.store-order-status',
            with: [
                'order' => $this->order,
                'storeName' => $this->storeName,
                'statusLabel' => self::STATUS_LABELS[$this->order->status] ?? $this->order->status,
                'statusLabels' => self::STATUS_LABELS,
            ],
        );
    }
}
