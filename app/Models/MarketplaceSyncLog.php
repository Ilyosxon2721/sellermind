<?php

// file: app/Models/MarketplaceSyncLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    public const TYPE_PRODUCTS = 'products';

    public const TYPE_PRICES = 'prices';

    public const TYPE_STOCKS = 'stocks';

    public const TYPE_ORDERS = 'orders';

    public const TYPE_REPORTS = 'reports';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'marketplace_account_id',
        'type',
        'status',
        'started_at',
        'finished_at',
        'message',
        'request_payload',
        'response_payload',
    ];

    protected $appends = ['duration', 'status_label', 'type_label'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Start sync log
     */
    public static function start(int $accountId, string $type, ?array $requestPayload = null): self
    {
        return self::create([
            'marketplace_account_id' => $accountId,
            'type' => $type,
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
            'request_payload' => $requestPayload,
        ]);
    }

    /**
     * Mark as success
     */
    public function markAsSuccess(?string $message = null, ?array $responsePayload = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'finished_at' => now(),
            'message' => $message,
            'response_payload' => $responsePayload,
        ]);
    }

    /**
     * Mark as error
     */
    public function markAsError(string $message, ?array $responsePayload = null): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'finished_at' => now(),
            'message' => $message,
            'response_payload' => $responsePayload,
        ]);
    }

    /**
     * Длительность синхронизации в секундах
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->started_at || ! $this->finished_at) {
                    return null;
                }

                return max(0, $this->finished_at->timestamp - $this->started_at->timestamp);
            }
        );
    }

    /**
     * Обратная совместимость: прямой вызов getDuration()
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * Метка статуса
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                self::STATUS_PENDING => 'Ожидает',
                self::STATUS_RUNNING => 'Выполняется',
                self::STATUS_SUCCESS => 'Успешно',
                self::STATUS_ERROR => 'Ошибка',
                default => $this->status,
            }
        );
    }

    /**
     * Обратная совместимость: прямой вызов getStatusLabel()
     */
    public function getStatusLabel(): string
    {
        return $this->status_label ?? $this->status;
    }

    /**
     * Метка типа
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                self::TYPE_PRODUCTS => 'Товары',
                self::TYPE_PRICES => 'Цены',
                self::TYPE_STOCKS => 'Остатки',
                self::TYPE_ORDERS => 'Заказы',
                self::TYPE_REPORTS => 'Отчёты',
                default => $this->type,
            }
        );
    }

    /**
     * Обратная совместимость: прямой вызов getTypeLabel()
     */
    public function getTypeLabel(): string
    {
        return $this->type_label ?? $this->type;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_SUCCESS => 'green',
            self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }
}