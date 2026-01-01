<?php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceAccountIssue;
use Illuminate\Support\Facades\Log;

class IssueDetectorService
{
    /**
     * Обработать ошибку API и зарегистрировать проблему если нужно
     */
    public function handleApiError(
        MarketplaceAccount $account,
        int $httpStatus,
        ?string $errorBody = null,
        ?array $errorDetails = [],
        string $context = ''
    ): ?MarketplaceAccountIssue {
        // Парсим тело ошибки
        $parsedError = $this->parseErrorBody($errorBody);
        $errorCode = $parsedError['code'] ?? null;
        $errorMessage = $parsedError['message'] ?? null;

        // Определяем тип и серьёзность проблемы
        $issueData = $this->detectIssueType($httpStatus, $errorCode, $errorMessage, $account->marketplace);

        if (!$issueData) {
            // Это не критичная ошибка, не регистрируем
            return null;
        }

        // Регистрируем проблему
        $issue = MarketplaceAccountIssue::reportIssue(
            $account,
            $issueData['type'],
            $issueData['title'],
            $issueData['description'],
            array_merge($errorDetails, [
                'error_body' => $errorBody,
                'context' => $context,
                'detected_at' => now()->toIso8601String(),
            ]),
            $httpStatus,
            $errorCode,
            $issueData['severity']
        );

        // Логируем
        Log::warning("Marketplace issue detected", [
            'account_id' => $account->id,
            'marketplace' => $account->marketplace,
            'issue_type' => $issueData['type'],
            'severity' => $issueData['severity'],
            'http_status' => $httpStatus,
            'error_code' => $errorCode,
        ]);

        return $issue;
    }

    /**
     * Парсить тело ошибки (JSON)
     */
    protected function parseErrorBody(?string $errorBody): array
    {
        if (!$errorBody) {
            return [];
        }

        try {
            $decoded = json_decode($errorBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'code' => $decoded['error_code'] ?? $decoded['code'] ?? $decoded['errors'][0]['code'] ?? null,
                    'message' => $decoded['error'] ?? $decoded['message'] ?? $decoded['errors'][0]['message'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return [];
    }

    /**
     * Определить тип проблемы по HTTP статусу и коду ошибки
     */
    protected function detectIssueType(
        int $httpStatus,
        ?string $errorCode,
        ?string $errorMessage,
        string $marketplace
    ): ?array {
        // 401 Unauthorized - недействительный или истёкший токен
        if ($httpStatus === 401) {
            return [
                'type' => 'token_invalid',
                'severity' => 'critical',
                'title' => 'Недействительный API токен',
                'description' => 'API токен недействителен или истёк. Необходимо обновить токен в настройках аккаунта.',
            ];
        }

        // 403 Forbidden - недостаточно прав
        if ($httpStatus === 403) {
            // Uzum специфичные коды
            if ($marketplace === 'uzum') {
                if ($errorCode === 'open-api-005' || str_contains($errorMessage ?? '', 'Shops ids is not available')) {
                    return [
                        'type' => 'shop_access_denied',
                        'severity' => 'critical',
                        'title' => 'Нет доступа к магазинам',
                        'description' => 'API токен не имеет доступа к указанным магазинам. Проверьте права токена в личном кабинете Uzum.',
                    ];
                }
            }

            return [
                'type' => 'insufficient_permissions',
                'severity' => 'critical',
                'title' => 'Недостаточно прав доступа',
                'description' => 'API токен не имеет необходимых прав. Проверьте права токена в личном кабинете маркетплейса.',
            ];
        }

        // 429 Too Many Requests - превышен лимит
        if ($httpStatus === 429) {
            return [
                'type' => 'rate_limit',
                'severity' => 'warning',
                'title' => 'Превышен лимит запросов',
                'description' => 'Превышен лимит запросов к API. Синхронизация будет возобновлена автоматически через некоторое время.',
            ];
        }

        // 500, 502, 503, 504 - проблемы на стороне сервера
        if ($httpStatus >= 500 && $httpStatus < 600) {
            return [
                'type' => 'api_error',
                'severity' => 'warning',
                'title' => 'Ошибка API маркетплейса',
                'description' => 'API маркетплейса временно недоступен. Повторная попытка будет выполнена автоматически.',
            ];
        }

        // Остальные ошибки 4xx
        if ($httpStatus >= 400 && $httpStatus < 500) {
            return [
                'type' => 'api_error',
                'severity' => 'warning',
                'title' => "Ошибка API ({$httpStatus})",
                'description' => $errorMessage ?? 'Ошибка при обращении к API маркетплейса.',
            ];
        }

        return null;
    }

    /**
     * Проверить и автоматически решить проблему если она исправлена
     */
    public function checkAndResolveIssues(MarketplaceAccount $account): void
    {
        // Получаем активные проблемы аккаунта
        $issues = MarketplaceAccountIssue::where('marketplace_account_id', $account->id)
            ->where('status', 'active')
            ->get();

        foreach ($issues as $issue) {
            // Пробуем определить решена ли проблема
            // Например, если последняя проблема была более 24 часов назад
            if ($issue->last_occurred_at->diffInHours(now()) > 24) {
                $issue->markAsResolved();
                Log::info("Marketplace issue auto-resolved", [
                    'issue_id' => $issue->id,
                    'account_id' => $account->id,
                    'type' => $issue->type,
                ]);
            }
        }
    }

    /**
     * Получить рекомендации по устранению проблемы
     */
    public function getResolutionSteps(string $issueType, string $marketplace): array
    {
        $baseSteps = match($issueType) {
            'token_invalid', 'token_expired' => [
                '1. Войдите в личный кабинет маркетплейса',
                '2. Перейдите в раздел API / Интеграции',
                '3. Создайте новый API токен или обновите существующий',
                '4. Скопируйте новый токен',
                '5. Обновите токен в настройках аккаунта',
            ],
            'shop_access_denied' => [
                '1. Войдите в личный кабинет маркетплейса',
                '2. Проверьте какие магазины доступны для вашего аккаунта',
                '3. Убедитесь что API токен имеет доступ к этим магазинам',
                '4. При необходимости создайте новый токен с нужными правами',
                '5. Обновите токен в настройках аккаунта',
            ],
            'insufficient_permissions' => [
                '1. Войдите в личный кабинет маркетплейса',
                '2. Перейдите в раздел API / Интеграции',
                '3. Проверьте права (permissions) токена',
                '4. Создайте новый токен с необходимыми правами:',
                '   - Чтение заказов (orders:read)',
                '   - Чтение товаров (products:read)',
                '   - Чтение финансов (finances:read)',
                '5. Обновите токен в настройках аккаунта',
            ],
            'rate_limit' => [
                '1. Подождите некоторое время (обычно 1-5 минут)',
                '2. Синхронизация возобновится автоматически',
                '3. Если проблема повторяется часто - уменьшите частоту синхронизации',
            ],
            'api_error' => [
                '1. Проверьте статус API маркетплейса на официальном сайте',
                '2. Подождите некоторое время',
                '3. Синхронизация возобновится автоматически',
                '4. Если проблема не исчезает > 1 часа - обратитесь в поддержку маркетплейса',
            ],
            default => [
                '1. Проверьте настройки аккаунта',
                '2. Убедитесь что API токен активен',
                '3. Попробуйте переподключить аккаунт',
            ],
        };

        return $baseSteps;
    }
}
