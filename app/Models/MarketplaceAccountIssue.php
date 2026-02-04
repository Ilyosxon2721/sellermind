<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAccountIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'company_id',
        'type',
        'severity',
        'title',
        'description',
        'error_details',
        'http_status',
        'error_code',
        'status',
        'resolved_at',
        'occurrences',
        'last_occurred_at',
    ];

    protected $casts = [
        'error_details' => 'array',
        'resolved_at' => 'datetime',
        'last_occurred_at' => 'datetime',
        'occurrences' => 'integer',
        'http_status' => 'integer',
    ];

    /**
     * Связь с аккаунтом маркетплейса
     */
    public function account()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Связь с компанией
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Создать или обновить проблему
     */
    public static function reportIssue(
        MarketplaceAccount $account,
        string $type,
        string $title,
        ?string $description = null,
        array $errorDetails = [],
        ?int $httpStatus = null,
        ?string $errorCode = null,
        string $severity = 'warning'
    ): self {
        // Проверяем есть ли активная проблема такого же типа
        $issue = self::where('marketplace_account_id', $account->id)
            ->where('type', $type)
            ->where('status', 'active')
            ->first();

        if ($issue) {
            // Обновляем существующую проблему
            $issue->update([
                'occurrences' => $issue->occurrences + 1,
                'last_occurred_at' => now(),
                'description' => $description ?? $issue->description,
                'error_details' => array_merge($issue->error_details ?? [], $errorDetails),
                'http_status' => $httpStatus ?? $issue->http_status,
                'error_code' => $errorCode ?? $issue->error_code,
                'severity' => $severity,
            ]);
        } else {
            // Создаём новую проблему
            $issue = self::create([
                'marketplace_account_id' => $account->id,
                'company_id' => $account->company_id,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'error_details' => $errorDetails,
                'http_status' => $httpStatus,
                'error_code' => $errorCode,
                'status' => 'active',
                'occurrences' => 1,
                'last_occurred_at' => now(),
            ]);
        }

        return $issue;
    }

    /**
     * Отметить проблему как решённую
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Игнорировать проблему
     */
    public function markAsIgnored(): void
    {
        $this->update([
            'status' => 'ignored',
        ]);
    }

    /**
     * Получить человекочитаемое описание типа проблемы
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'token_invalid' => 'Недействительный токен',
            'token_expired' => 'Токен истёк',
            'insufficient_permissions' => 'Недостаточно прав',
            'shop_access_denied' => 'Нет доступа к магазинам',
            'api_error' => 'Ошибка API',
            'rate_limit' => 'Превышен лимит запросов',
            'sync_failed' => 'Ошибка синхронизации',
            'connection_failed' => 'Ошибка подключения',
            default => $this->type,
        };
    }

    /**
     * Получить цвет badge в зависимости от severity
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Получить иконку в зависимости от severity
     */
    public function getSeverityIcon(): string
    {
        return match ($this->severity) {
            'critical' => '🔴',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '⚪',
        };
    }

    /**
     * Scope для активных проблем
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope для критических проблем
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope для проблем компании
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
