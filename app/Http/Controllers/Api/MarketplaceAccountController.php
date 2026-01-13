<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $accounts = MarketplaceAccount::where('company_id', $request->company_id)->get();

        $response = response()->json([
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'marketplace' => $a->marketplace,
                'name' => $a->name,
                'marketplace_label' => MarketplaceAccount::getMarketplaceLabels()[$a->marketplace] ?? $a->marketplace,
                'display_name' => $a->getDisplayName(),
                'is_active' => $a->is_active,
                'connected_at' => $a->connected_at,
            ]),
            'available_marketplaces' => MarketplaceAccount::getMarketplaceLabels(),
        ]);

        // Add cache headers for better performance
        $response->header('Cache-Control', 'private, max-age=60');
        $response->header('ETag', md5($response->getContent()));

        return $response;
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'company_id' => ['required', 'exists:companies,id'],
                'marketplace' => ['required', 'string', 'in:uzum,wb,ozon,ym'],
                'name' => ['nullable', 'string', 'max:255'],
                'credentials' => ['required', 'array'],
                'account_id' => ['nullable', 'exists:marketplace_accounts,id'], // Для обновления существующего аккаунта
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors(),
                'error' => implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))
            ], 422);
        }

        if (!$request->user()->isOwnerOf($request->company_id)) {
            return response()->json(['message' => 'Только владелец может подключать маркетплейсы.'], 403);
        }

        // Валидация credentials в зависимости от маркетплейса
        $validationError = $this->validateCredentials($request->marketplace, $request->credentials);
        if ($validationError) {
            return response()->json([
                'message' => 'Ошибка в учётных данных',
                'error' => $validationError,
                'received_credentials' => array_keys($request->credentials ?? []) // Показываем какие поля получили
            ], 422);
        }

        // Если передан account_id, обновляем существующий аккаунт
        if ($request->account_id) {
            $existing = MarketplaceAccount::where('id', $request->account_id)
                ->where('company_id', $request->company_id)
                ->firstOrFail();

            $existing->credentials = $request->credentials;

            // Обновляем имя если передано
            if ($request->has('name')) {
                $existing->name = $request->name;
            }

            // For Wildberries: save category-specific tokens
            if ($request->marketplace === 'wb') {
                $creds = $request->credentials;
                $existing->wb_content_token = $creds['wb_content_token'] ?? null;
                $existing->wb_marketplace_token = $creds['wb_marketplace_token'] ?? null;
                $existing->wb_prices_token = $creds['wb_prices_token'] ?? null;
                $existing->wb_statistics_token = $creds['wb_statistics_token'] ?? null;
            }

            // For Uzum: save API token to dedicated field
            if ($request->marketplace === 'uzum') {
                $creds = $request->credentials;
                $existing->uzum_api_key = $creds['api_token'] ?? null;
            }

            $existing->save();

            // Тестируем новые credentials
            $testResult = $this->testConnection($existing);

            if (!$testResult['success']) {
                // Если тест не прошёл, помечаем аккаунт как неактивный
                $existing->update(['is_active' => false]);

                return response()->json([
                    'message' => 'Учётные данные обновлены, но подключение не удалось',
                    'error' => $testResult['error'],
                    'account' => [
                        'id' => $existing->id,
                        'marketplace' => $existing->marketplace,
                        'name' => $existing->name,
                        'is_active' => false,
                        'connected_at' => $existing->connected_at,
                    ],
                    'warning' => 'Проверьте правильность API токенов. Аккаунт временно отключён.',
                ], 200);
            }

            $existing->markAsConnected();

            return response()->json([
                'message' => 'Учётные данные обновлены и успешно проверены! ' . $testResult['message'],
                'account' => [
                    'id' => $existing->id,
                    'marketplace' => $existing->marketplace,
                    'name' => $existing->name,
                    'is_active' => $existing->is_active,
                    'connected_at' => $existing->connected_at,
                ],
                'test_result' => $testResult,
            ]);
        }

        // Создаём новый аккаунт (разрешаем несколько аккаунтов одного маркетплейса)
        $accountData = [
            'company_id' => $request->company_id,
            'marketplace' => $request->marketplace,
            'name' => $request->name, // Имя для различения аккаунтов
            'credentials' => $request->credentials,
        ];

        // For Wildberries: save category-specific tokens
        if ($request->marketplace === 'wb') {
            $creds = $request->credentials;
            $accountData['wb_content_token'] = $creds['wb_content_token'] ?? null;
            $accountData['wb_marketplace_token'] = $creds['wb_marketplace_token'] ?? null;
            $accountData['wb_prices_token'] = $creds['wb_prices_token'] ?? null;
            $accountData['wb_statistics_token'] = $creds['wb_statistics_token'] ?? null;
        }

        // For Uzum: save API token to dedicated field
        if ($request->marketplace === 'uzum') {
            $creds = $request->credentials;
            $accountData['uzum_api_key'] = $creds['api_token'] ?? null;
        }

        $account = MarketplaceAccount::create($accountData);
        $account->markAsConnected();

        // Тестируем подключение к API
        $testResult = $this->testConnection($account);

        if (!$testResult['success']) {
            // Если тест не прошёл, помечаем аккаунт как неактивный
            $account->update(['is_active' => false]);

            return response()->json([
                'message' => 'Аккаунт создан, но подключение не удалось',
                'error' => $testResult['error'],
                'account' => [
                    'id' => $account->id,
                    'marketplace' => $account->marketplace,
                    'name' => $account->name,
                    'is_active' => false,
                    'connected_at' => $account->connected_at,
                ],
                'warning' => 'Проверьте правильность API токенов. Аккаунт отключён до успешного подключения.',
            ], 201);
        }

        // For Uzum: automatically fetch and store shops
        $shopsInfo = '';
        if ($account->marketplace === 'uzum') {
            try {
                $httpClient = new \App\Services\Marketplaces\MarketplaceHttpClient($account, 'uzum');
                $uzumClient = new \App\Services\Marketplaces\UzumClient(
                    $httpClient,
                    app(\App\Services\Marketplaces\IssueDetectorService::class)
                );

                $shops = $uzumClient->fetchShops($account);

                if (!empty($shops)) {
                    // Store shops in database
                    foreach ($shops as $shop) {
                        if (isset($shop['id'])) {
                            \App\Models\MarketplaceShop::updateOrCreate([
                                'marketplace_account_id' => $account->id,
                                'external_id' => (string) $shop['id'],
                            ], [
                                'name' => $shop['name'] ?? null,
                                'raw_payload' => $shop,
                            ]);
                        }
                    }

                    // Update account with comma-separated shop IDs
                    $shopIds = array_column($shops, 'id');
                    $account->update(['shop_id' => implode(',', $shopIds)]);

                    $shopNames = array_column($shops, 'name');
                    $shopsInfo = ' Найдено магазинов: ' . count($shops) . ' (' . implode(', ', array_slice($shopNames, 0, 3)) . ')';
                    if (count($shops) > 3) {
                        $shopsInfo .= '...';
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to auto-fetch Uzum shops', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);
                $shopsInfo = ' Магазины будут загружены позже.';
            }
        }

        return response()->json([
            'message' => 'Маркетплейс успешно подключён! ' . $testResult['message'] . $shopsInfo,
            'account' => [
                'id' => $account->id,
                'marketplace' => $account->marketplace,
                'name' => $account->name,
                'is_active' => $account->is_active,
                'connected_at' => $account->connected_at,
            ],
            'test_result' => $testResult,
        ], 201);
    }

    public function destroy(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->isOwnerOf($account->company_id)) {
            return response()->json(['message' => 'Только владелец может удалять маркетплейсы.'], 403);
        }

        // Store account name for response message
        $accountName = $account->getDisplayName();

        // Delete related data first (cascade delete is handled by database foreign keys)
        // But we can explicitly clean up some data if needed
        try {
            // Delete the account
            $account->delete();

            return response()->json([
                'message' => "Аккаунт {$accountName} успешно удалён.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Не удалось удалить аккаунт: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function test(Request $request, MarketplaceAccount $account, MarketplaceSyncService $syncService): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $result = $syncService->testConnection($account);

        return response()->json($result);
    }

    public function show(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Get credentials and mask sensitive values
        $credentials = $account->getAllCredentials();
        $maskedCredentials = $this->maskCredentials($credentials);
        
        // Get credentials_json if exists
        $credentialsJson = $account->credentials_json ?? [];

        return response()->json([
            'account' => [
                'id' => $account->id,
                'marketplace' => $account->marketplace,
                'marketplace_type' => $account->marketplace,
                'marketplace_label' => MarketplaceAccount::getMarketplaceLabels()[$account->marketplace] ?? $account->marketplace,
                'name' => $account->name,
                'display_name' => $account->getDisplayName(),
                'shop_id' => $account->shop_id,
                'is_active' => $account->is_active,
                'has_api_key' => !empty($account->api_key),
                'connected_at' => $account->connected_at,
                'products_count' => $account->products()->count(),
                'orders_count' => $this->getOrdersCount($account),
                // Include masked credentials for display
                'credentials' => $maskedCredentials,
                'credentials_display' => $this->getCredentialsDisplay($account),
            ],
        ]);
    }
    
    /**
     * Mask sensitive credential values for display
     */
    protected function maskCredentials(array $credentials): array
    {
        $masked = [];
        $sensitiveFields = [
            'api_key', 'api_token', 'oauth_token', 'oauth_refresh_token',
            'client_secret', 'uzum_api_key', 'uzum_access_token', 'uzum_refresh_token',
            'uzum_client_secret', 'wb_content_token', 'wb_marketplace_token',
            'wb_prices_token', 'wb_statistics_token'
        ];
        
        foreach ($credentials as $key => $value) {
            if (in_array($key, $sensitiveFields) && !empty($value)) {
                // Show first 8 and last 4 chars of token
                $len = strlen($value);
                if ($len > 16) {
                    $masked[$key] = substr($value, 0, 8) . '...' . substr($value, -4);
                } else {
                    $masked[$key] = '***настроен***';
                }
                $masked[$key . '_set'] = true;
            } else if (!empty($value)) {
                $masked[$key] = $value;
            }
        }
        
        return $masked;
    }
    
    /**
     * Get human-readable credentials display info
     */
    protected function getCredentialsDisplay(MarketplaceAccount $account): array
    {
        $display = [];
        
        switch ($account->marketplace) {
            case 'wb':
                $display[] = ['label' => 'API Token', 'value' => $account->api_key ? '✅ Настроен' : '❌ Не настроен'];
                $display[] = ['label' => 'Content Token', 'value' => $account->wb_content_token ? '✅ Настроен' : '—'];
                $display[] = ['label' => 'Marketplace Token', 'value' => $account->wb_marketplace_token ? '✅ Настроен' : '—'];
                $display[] = ['label' => 'Prices Token', 'value' => $account->wb_prices_token ? '✅ Настроен' : '—'];
                $display[] = ['label' => 'Statistics Token', 'value' => $account->wb_statistics_token ? '✅ Настроен' : '—'];
                break;
                
            case 'ozon':
                $creds = $account->getAllCredentials();
                $display[] = ['label' => 'Client ID', 'value' => $creds['client_id'] ?? '❌ Не настроен'];
                $display[] = ['label' => 'API Key', 'value' => !empty($creds['api_key']) ? '✅ Настроен' : '❌ Не настроен'];
                break;
                
            case 'uzum':
                $display[] = ['label' => 'API Token', 'value' => $account->uzum_access_token || $account->uzum_api_key || $account->api_key ? '✅ Настроен' : '❌ Не настроен'];
                $shops = $account->credentials_json['shop_ids'] ?? $account->getDecryptedCredentials()['shop_ids'] ?? [];
                $display[] = ['label' => 'Shop IDs', 'value' => !empty($shops) ? implode(', ', (array)$shops) : '❌ Не настроены'];
                break;
                
            case 'ym':
            case 'yandex_market':
                $creds = $account->getAllCredentials();
                $display[] = ['label' => 'API Key', 'value' => !empty($creds['api_key']) ? '✅ Настроен' : '❌ Не настроен'];
                $display[] = ['label' => 'Campaign ID', 'value' => $creds['campaign_id'] ?? '❌ Не настроен'];
                $display[] = ['label' => 'Business ID', 'value' => $creds['business_id'] ?? '—'];
                break;
        }
        
        return $display;
    }
    
    /**
     * Get orders count safely (handle different order models)
     */
    protected function getOrdersCount(MarketplaceAccount $account): int
    {
        try {
            return $account->orders()->count();
        } catch (\Throwable $e) {
            // Table may not exist or other error
            return 0;
        }
    }

    public function syncLogs(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $logs = MarketplaceSyncLog::where('marketplace_account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'logs' => $logs->map(fn($log) => [
                'id' => $log->id,
                'type' => $log->type,
                'type_label' => $log->getTypeLabel(),
                'status' => $log->status,
                'status_label' => $log->getStatusLabel(),
                'status_color' => $log->getStatusColor(),
                'message' => $log->message,
                'started_at' => $log->started_at,
                'finished_at' => $log->finished_at,
                'duration' => $log->getDuration(),
            ]),
        ]);
    }

    /**
     * Server-Sent Events stream for sync logs (fallback вместо WebSocket).
     * Авторизация: bearer-токен или ?token= (Sanctum PAT).
     */
    public function syncLogsStream(Request $request, MarketplaceAccount $account): StreamedResponse
    {
        $token = $request->bearerToken() ?: $request->query('token');
        if (!$token) {
            abort(401);
        }

        $pat = PersonalAccessToken::findToken($token);
        if (!$pat || !$pat->tokenable) {
            abort(401);
        }

        if (!$pat->tokenable->hasCompanyAccess($account->company_id)) {
            abort(403);
        }

        $lastId = (int) $request->query('last_id', 0);
        $timeoutSeconds = 20;
        $start = microtime(true);

        return response()->stream(function () use ($account, $lastId, $start, $timeoutSeconds) {
            echo "retry: 2000\n\n";
            @ob_flush();
            @flush();

            $currentLastId = $lastId;

            while (microtime(true) - $start < $timeoutSeconds) {
                $newLogs = MarketplaceSyncLog::query()
                    ->where('marketplace_account_id', $account->id)
                    ->when($currentLastId > 0, fn($q) => $q->where('id', '>', $currentLastId))
                    ->orderBy('id', 'asc')
                    ->take(50)
                    ->get();

                if ($newLogs->isNotEmpty()) {
                    $currentLastId = $newLogs->max('id');

                    $payload = $newLogs->map(function (MarketplaceSyncLog $log) {
                        return [
                            'id' => $log->id,
                            'type' => $log->type,
                            'type_label' => $log->getTypeLabel(),
                            'status' => $log->status,
                            'status_label' => $log->getStatusLabel(),
                            'status_color' => $log->getStatusColor(),
                            'message' => $log->message,
                            'started_at' => $log->started_at,
                            'finished_at' => $log->finished_at,
                            'duration' => $log->getDuration(),
                        ];
                    })->values();

                    echo "event: logs\n";
                    echo 'data: ' . json_encode([
                        'last_id' => $currentLastId,
                        'logs' => $payload,
                    ]) . "\n\n";
                    @ob_flush();
                    @flush();
                }

                usleep(500000); // 0.5s
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Start real-time monitoring for marketplace account
     */
    public function startMonitoring(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Запускаем мониторинг
        \App\Jobs\Marketplace\MonitorMarketplaceChangesJob::dispatch($account);

        return response()->json([
            'message' => 'Мониторинг запущен',
            'success' => true,
        ]);
    }

    /**
     * Stop real-time monitoring for marketplace account
     */
    public function stopMonitoring(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Останавливаем мониторинг (удаляем pending jobs из очереди)
        \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', config('queue.default'))
            ->where('payload', 'like', '%MonitorMarketplaceChangesJob%')
            ->where('payload', 'like', '%"id":' . $account->id . '%')
            ->delete();

        return response()->json([
            'message' => 'Мониторинг остановлен',
            'success' => true,
        ]);
    }

    /**
     * Получить требования к полям и инструкции для добавления аккаунта маркетплейса
     */
    public function requirements(Request $request): JsonResponse
    {
        $marketplace = $request->query('marketplace');

        if (!$marketplace) {
            return response()->json([
                'message' => 'Укажите маркетплейс в параметре marketplace (wb, uzum, ozon, ym)'
            ], 400);
        }

        $requirements = $this->getMarketplaceRequirements($marketplace);

        if (!$requirements) {
            return response()->json([
                'message' => 'Неизвестный маркетплейс: ' . $marketplace
            ], 404);
        }

        return response()->json($requirements);
    }

    /**
     * Получить информацию о требованиях для конкретного маркетплейса
     */
    protected function getMarketplaceRequirements(string $marketplace): ?array
    {
        $requirements = [
            'wb' => [
                'marketplace' => 'wb',
                'name' => 'Wildberries',
                'description' => 'Для подключения к Wildberries необходимо создать API токен в личном кабинете',
                'setup_guide' => [
                    'title' => 'Как создать токен Wildberries?',
                    'subtitle' => 'Рекомендуем создать один универсальный токен со всеми разделами - это проще и удобнее.',
                    'link' => 'https://seller.wildberries.ru/supplier-settings/access-to-api',
                    'link_text' => 'Открыть ЛК Wildberries',
                    'recommended_approach' => [
                        'title' => '✅ Рекомендуемый способ: Универсальный токен',
                        'description' => 'Создайте один токен со всеми необходимыми правами - это самый простой и удобный вариант',
                        'field_name' => 'api_token',
                        'steps' => [
                            'ЛК WB → Настройки → Доступ к API → Создать токен'
                        ],
                        'permissions' => [
                            '✓ Контент - для работы с товарами и медиа',
                            '✓ Маркетплейс - для заказов и управления',
                            '✓ Поставки - для работы с поставками',
                            '✓ Цены и скидки - для управления ценами',
                            '✓ Статистика - для получения статистики продаж',
                            '✓ Аналитика - для аналитических данных',
                            '✓ Финансы - для финансовых данных (опционально)',
                            '✓ Возвраты - для работы с возвратами'
                        ]
                    ],
                    'alternative_approach' => [
                        'title' => '⚙️ Альтернативный способ: Отдельные токены',
                        'description' => 'Для продвинутых пользователей: создайте отдельные токены для каждого API',
                        'tokens' => [
                            [
                                'number' => 1,
                                'name' => 'Content API Token (Контент)',
                                'field_name' => 'wb_content_token',
                                'permissions' => [
                                    '✓ Контент → Управление контентом',
                                    '✓ Контент → Карточки и медиа'
                                ]
                            ],
                            [
                                'number' => 2,
                                'name' => 'Marketplace API Token (Маркетплейс)',
                                'field_name' => 'wb_marketplace_token',
                                'permissions' => [
                                    '✓ Маркетплейс → Просмотр',
                                    '✓ Поставки → Управление',
                                    '✓ Возвраты → Управление'
                                ]
                            ],
                            [
                                'number' => 3,
                                'name' => 'Prices API Token (Цены)',
                                'field_name' => 'wb_prices_token',
                                'permissions' => [
                                    '✓ Цены и скидки → Управление'
                                ]
                            ],
                            [
                                'number' => 4,
                                'name' => 'Statistics API Token (Аналитика)',
                                'field_name' => 'wb_statistics_token',
                                'permissions' => [
                                    '✓ Статистика → Просмотр',
                                    '✓ Аналитика → Просмотр'
                                ]
                            ]
                        ]
                    ],
                    'quick_tip' => 'Быстрая шпаргалка',
                    'detailed_guide' => 'Подробная инструкция'
                ],
                'fields' => [
                    [
                        'name' => 'name',
                        'label' => 'Название аккаунта',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'Например: Мой магазин WB',
                        'help' => 'Произвольное название для различения аккаунтов. Если не указано, будет сгенерировано автоматически.'
                    ],
                    [
                        'name' => 'api_token',
                        'label' => '✅ API токен (универсальный) - РЕКОМЕНДУЕТСЯ',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6...',
                        'help' => '👍 Универсальный токен с доступом ко всем API. Это самый простой и удобный способ - создайте один токен со всеми разделами.'
                    ],
                    [
                        'name' => 'wb_content_token',
                        'label' => '⚙️ Content API Token (Товары) - опционально',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6...',
                        'help' => 'Только если не используете универсальный токен. Токен для работы с товарами и контентом.'
                    ],
                    [
                        'name' => 'wb_marketplace_token',
                        'label' => '⚙️ Marketplace API Token (Заказы) - опционально',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6...',
                        'help' => 'Только если не используете универсальный токен. Токен для работы с заказами и поставками.'
                    ],
                    [
                        'name' => 'wb_prices_token',
                        'label' => '⚙️ Prices API Token (Цены) - опционально',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6...',
                        'help' => 'Только если не используете универсальный токен. Токен для работы с ценами.'
                    ],
                    [
                        'name' => 'wb_statistics_token',
                        'label' => '⚙️ Statistics API Token (Аналитика) - опционально',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6...',
                        'help' => 'Только если не используете универсальный токен. Токен для работы со статистикой.'
                    ],
                ],
                'instructions' => [
                    'title' => '✅ Рекомендуемый способ: Создать универсальный токен',
                    'steps' => [
                        'Войдите в личный кабинет Wildberries Seller',
                        'Перейдите в раздел "Настройки" → "Доступ к API"',
                        'Нажмите "Создать новый токен"',
                        'Выберите ВСЕ необходимые разделы доступа:',
                        '  ✓ Контент - для работы с товарами и медиа',
                        '  ✓ Маркетплейс - для заказов и управления',
                        '  ✓ Поставки - для работы с поставками',
                        '  ✓ Цены и скидки - для управления ценами',
                        '  ✓ Статистика - для получения статистики продаж',
                        '  ✓ Аналитика - для аналитических данных',
                        '  ✓ Финансы - для финансовых данных (опционально)',
                        '  ✓ Возвраты - для работы с возвратами',
                        'Нажмите "Создать" и скопируйте токен',
                        'Вставьте токен в поле "API токен (универсальный)"',
                        '⚠️ ВАЖНО: Токен показывается только один раз! Сохраните его в безопасном месте.'
                    ],
                    'notes' => [
                        '👍 РЕКОМЕНДУЕТСЯ: Используйте один универсальный токен со всеми правами - это проще и удобнее',
                        '⚙️ Альтернатива: Можно создать отдельные токены для каждого API (только для продвинутых пользователей)',
                        'Токен должен быть в формате JWT (начинается с eyJhbGc...)',
                        'Если синхронизация не работает - проверьте что выбраны все необходимые разделы в личном кабинете WB'
                    ]
                ],
                'validation' => [
                    'required_one_of' => ['api_token', 'wb_content_token', 'wb_marketplace_token', 'wb_prices_token', 'wb_statistics_token'],
                    'token_format' => 'base64',
                    'min_length' => 20
                ]
            ],

            'uzum' => [
                'marketplace' => 'uzum',
                'name' => 'Uzum Market',
                'description' => 'Для подключения к Uzum Market необходимо создать API токен. Магазины будут подключены автоматически.',
                'setup_guide' => [
                    'title' => 'Как создать токен Uzum Market?',
                    'subtitle' => 'Uzum Market использует один API токен для доступа ко всем функциям. Все доступные магазины будут подключены автоматически.',
                    'link' => 'https://seller.uzum.uz/integration/api',
                    'link_text' => 'Открыть ЛК Uzum Market',
                    'tokens' => [
                        [
                            'number' => 1,
                            'name' => 'API Token',
                            'field_name' => 'api_token',
                            'steps' => [
                                'ЛК Uzum → Интеграции → API → Создать токен'
                            ],
                            'permissions' => [
                                '✓ Полный доступ ко всем API',
                                'ℹ️ Все доступные магазины будут подключены автоматически'
                            ]
                        ]
                    ],
                    'quick_tip' => 'Быстрая шпаргалка',
                    'detailed_guide' => 'Подробная инструкция'
                ],
                'fields' => [
                    [
                        'name' => 'name',
                        'label' => 'Название аккаунта',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'Например: Мой магазин Uzum',
                        'help' => 'Произвольное название для различения аккаунтов'
                    ],
                    [
                        'name' => 'api_token',
                        'label' => 'API Token',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'w/77NI6IG8xzWK5sUj4An8...',
                        'help' => 'Токен для доступа к API Uzum Market. Все доступные магазины будут подключены автоматически.'
                    ],
                ],
                'instructions' => [
                    'title' => 'Где получить API токен Uzum Market:',
                    'steps' => [
                        'Войдите в личный кабинет Uzum Market',
                        'Перейдите в раздел "Интеграции" → "API"',
                        'Нажмите "Создать новый API токен"',
                        'Скопируйте созданный токен',
                        'Вставьте токен в форму подключения',
                        'Все доступные магазины будут автоматически найдены и подключены'
                    ],
                    'notes' => [
                        'API токен должен иметь доступ к магазинам',
                        'Все магазины, доступные токену, будут подключены автоматически',
                        'Не нужно вручную указывать ID магазинов - система получит их через API',
                        'После подключения вы увидите список подключенных магазинов'
                    ]
                ],
                'validation' => [
                    'required_fields' => ['api_token']
                ]
            ],

            'ozon' => [
                'marketplace' => 'ozon',
                'name' => 'Ozon',
                'description' => 'Для подключения к Ozon необходимо создать API ключ в личном кабинете Ozon Seller',
                'setup_guide' => [
                    'title' => 'Как создать API ключ для OZON?',
                    'subtitle' => 'OZON использует Client-Id и API-ключ для доступа ко всем функциям маркетплейса.',
                    'link' => 'https://seller.ozon.ru/app/settings/api-keys',
                    'link_text' => 'Открыть ЛК OZON Seller',
                    'tokens' => [
                        [
                            'number' => 1,
                            'name' => 'Client-Id (Идентификатор клиента)',
                            'field_name' => 'client_id',
                            'steps' => [
                                'ЛК OZON Seller → Настройки → API ключи → Сгенерировать ключ'
                            ],
                            'permissions' => [
                                'ℹ️ Числовой идентификатор вашего магазина',
                                'ℹ️ Копируется вместе с API-ключом'
                            ]
                        ],
                        [
                            'number' => 2,
                            'name' => 'API-ключ (секретный ключ)',
                            'field_name' => 'api_key',
                            'steps' => [
                                'Выберите ВСЕ необходимые права доступа'
                            ],
                            'permissions' => [
                                '✓ Товары - создание, редактирование, удаление',
                                '✓ Цены и остатки - управление ценами и остатками',
                                '✓ Заказы - просмотр и управление заказами',
                                '✓ Финансы - просмотр финансовых данных',
                                '✓ Аналитика - доступ к статистике и отчетам',
                                '⚠️ API-ключ показывается только ОДИН РАЗ!'
                            ]
                        ]
                    ],
                    'quick_tip' => 'Быстрая шпаргалка',
                    'detailed_guide' => 'Подробная инструкция'
                ],
                'fields' => [
                    [
                        'name' => 'name',
                        'label' => 'Название аккаунта',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'Например: Мой магазин Ozon',
                        'help' => 'Произвольное название для различения аккаунтов'
                    ],
                    [
                        'name' => 'client_id',
                        'label' => 'Client ID',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => '123456',
                        'help' => 'Идентификатор клиента из личного кабинета Ozon'
                    ],
                    [
                        'name' => 'api_key',
                        'label' => 'API ключ',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'ваш_api_ключ_от_ozon',
                        'help' => 'API ключ для доступа к Ozon API'
                    ],
                ],
                'instructions' => [
                    'title' => 'Где получить Client ID и API ключ Ozon:',
                    'steps' => [
                        'Войдите в личный кабинет Ozon Seller',
                        'Перейдите в раздел "Настройки" → "API ключи"',
                        'Нажмите "Сгенерировать ключ"',
                        'Выберите необходимые права доступа (рекомендуется выбрать все)',
                        'Нажмите "Создать"',
                        'Скопируйте Client ID и API Key',
                        '⚠️ ВАЖНО: API Key показывается только один раз!'
                    ],
                    'notes' => [
                        'Client ID - это числовой идентификатор вашего магазина',
                        'API Key - это секретный ключ для доступа к API',
                        'Один ключ может использоваться для всех операций с магазином',
                        'Рекомендуется создать отдельный ключ для каждого приложения'
                    ]
                ],
                'validation' => [
                    'required_fields' => ['client_id', 'api_key']
                ]
            ],

            'ym' => [
                'marketplace' => 'ym',
                'name' => 'Яндекс.Маркет',
                'description' => 'Для подключения к Яндекс.Маркет необходимо получить API-ключ или OAuth токен',
                'setup_guide' => [
                    'title' => 'Как подключиться к Яндекс.Маркет?',
                    'subtitle' => 'Яндекс.Маркет использует API-ключ (рекомендуется) или OAuth токен для доступа к Partner API.',
                    'link' => 'https://partner.market.yandex.ru/settings/api',
                    'link_text' => 'Открыть ЛК Яндекс.Маркет',
                    'tokens' => [
                        [
                            'number' => 1,
                            'name' => 'API-ключ (рекомендуется)',
                            'field_name' => 'oauth_token',
                            'steps' => [
                                'Войдите в личный кабинет Партнерского интерфейса',
                                'Перейдите в раздел "Настройки" → "API"',
                                'Нажмите "Сгенерировать новый API-ключ"',
                                'Скопируйте полученный ключ',
                            ],
                            'permissions' => [
                                '✓ Управление товарами и ценами',
                                '✓ Получение информации о заказах',
                                '✓ Управление остатками',
                                '✓ Доступ к отчетам и аналитике',
                                '⚠️ API-ключ показывается только один раз!'
                            ]
                        ],
                        [
                            'number' => 2,
                            'name' => 'ID кампании (campaign_id)',
                            'field_name' => 'campaign_id',
                            'steps' => [
                                'В личном кабинете Яндекс.Маркет',
                                'Перейдите в "Настройки" → "Информация о магазине"',
                                'Найдите "ID кампании" (числовой идентификатор)',
                                'Или используйте API метод GET /campaigns',
                            ],
                            'permissions' => [
                                'ℹ️ Технический идентификатор вашего магазина',
                                'ℹ️ Используется во всех запросах к API',
                                'ℹ️ Можно найти в URL личного кабинета'
                            ]
                        ]
                    ]
                ],
                'fields' => [
                    [
                        'name' => 'name',
                        'label' => 'Название аккаунта',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'Например: Мой магазин на Яндекс.Маркет',
                        'help' => 'Произвольное название для различения аккаунтов'
                    ],
                    [
                        'name' => 'oauth_token',
                        'label' => 'API-ключ / OAuth токен',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'ваш_api_ключ_или_oauth_токен',
                        'help' => 'API-ключ (рекомендуется) или OAuth токен для доступа к Partner API'
                    ],
                    [
                        'name' => 'campaign_id',
                        'label' => 'ID кампании',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => '12345678',
                        'help' => 'Идентификатор вашей кампании на Яндекс.Маркет'
                    ],
                ],
                'instructions' => [
                    'title' => 'Где получить API-ключ и ID кампании Яндекс.Маркет:',
                    'steps' => [
                        'Войдите в личный кабинет Яндекс.Маркет',
                        'Перейдите в раздел "Настройки" → "API"',
                        'Нажмите "Сгенерировать новый API-ключ"',
                        'Скопируйте полученный ключ',
                        'Перейдите в раздел "Настройки" → "Информация о магазине"',
                        'Найдите "ID кампании" и скопируйте его'
                    ],
                    'notes' => [
                        'API-ключ является рекомендуемым способом авторизации',
                        'ID кампании - это числовой идентификатор вашего магазина',
                        'Один ключ может использоваться для нескольких кампаний',
                        'API-ключ показывается только один раз при создании'
                    ]
                ],
                'validation' => [
                    'required_fields' => ['oauth_token', 'campaign_id']
                ]
            ],
        ];

        return $requirements[$marketplace] ?? null;
    }

    /**
     * Валидация credentials в зависимости от маркетплейса
     */
    protected function validateCredentials(string $marketplace, array $credentials): ?string
    {
        switch ($marketplace) {
            case 'wb':
                // Проверяем что есть хотя бы один токен
                $hasToken = !empty($credentials['api_token']) ||
                           !empty($credentials['wb_content_token']) ||
                           !empty($credentials['wb_marketplace_token']) ||
                           !empty($credentials['wb_prices_token']) ||
                           !empty($credentials['wb_statistics_token']);

                if (!$hasToken) {
                    return 'Для Wildberries необходимо указать хотя бы один API токен. ' .
                           'Вы можете указать универсальный токен (api_token) или отдельные токены для каждого API ' .
                           '(wb_content_token, wb_marketplace_token, wb_prices_token, wb_statistics_token).';
                }

                // Проверяем формат токенов (base64)
                $tokensToCheck = array_filter([
                    'api_token' => $credentials['api_token'] ?? null,
                    'wb_content_token' => $credentials['wb_content_token'] ?? null,
                    'wb_marketplace_token' => $credentials['wb_marketplace_token'] ?? null,
                    'wb_prices_token' => $credentials['wb_prices_token'] ?? null,
                    'wb_statistics_token' => $credentials['wb_statistics_token'] ?? null,
                ]);

                foreach ($tokensToCheck as $key => $token) {
                    if (!$this->isValidBase64Token($token)) {
                        return "Токен '{$key}' имеет неправильный формат. " .
                               "API токены Wildberries должны быть в формате base64 (например: eyJhbGc... или w/77NI6...).";
                    }
                }
                break;

            case 'uzum':
                if (empty($credentials['api_token'])) {
                    return 'Для Uzum необходимо указать API токен (api_token).';
                }
                // Shop IDs will be fetched automatically from API
                break;

            case 'ozon':
                if (empty($credentials['client_id'])) {
                    return 'Для Ozon необходимо указать Client ID (client_id). ' .
                           'Получить можно в личном кабинете: Настройки → API ключи.';
                }

                if (empty($credentials['api_key'])) {
                    return 'Для Ozon необходимо указать API ключ (api_key). ' .
                           'Получить можно в личном кабинете: Настройки → API ключи → Сгенерировать ключ.';
                }

                // Валидация формата Client-Id (должен быть числом)
                if (!is_numeric($credentials['client_id'])) {
                    return 'Client ID должен быть числом (например: 123456). ' .
                           'Проверьте правильность введенного Client ID.';
                }

                // Валидация формата API ключа (обычно UUID или длинная строка)
                if (strlen($credentials['api_key']) < 20) {
                    return 'API ключ слишком короткий. Убедитесь что вы скопировали полный ключ из личного кабинета OZON.';
                }

                // Проверка на UUID формат (OZON часто использует UUID)
                $apiKey = trim($credentials['api_key']);
                if (!preg_match('/^[a-f0-9\-]+$/i', $apiKey)) {
                    return 'API ключ содержит недопустимые символы. ' .
                           'API ключ OZON обычно состоит из букв (a-f), цифр (0-9) и дефисов.';
                }
                break;

            case 'ym':
                if (empty($credentials['oauth_token'])) {
                    return 'Для Яндекс.Маркет необходимо указать API-ключ или OAuth токен (oauth_token). ' .
                           'Получить можно в личном кабинете: Настройки → API → Сгенерировать новый API-ключ.';
                }

                if (empty($credentials['campaign_id'])) {
                    return 'Для Яндекс.Маркет необходимо указать ID кампании (campaign_id). ' .
                           'Найдите в личном кабинете: Настройки → Информация о магазине или используйте метод GET /campaigns.';
                }

                // Валидация формата campaign_id (должен быть числом)
                if (!is_numeric($credentials['campaign_id'])) {
                    return 'ID кампании должен быть числом (например: 12345678). ' .
                           'Проверьте правильность введенного ID кампании.';
                }

                // Валидация длины API ключа
                if (strlen($credentials['oauth_token']) < 20) {
                    return 'API-ключ слишком короткий. Убедитесь что вы скопировали полный ключ из личного кабинета Яндекс.Маркет.';
                }

                // Проверка формата токена (буквы, цифры, дефисы, подчеркивания)
                $token = trim($credentials['oauth_token']);
                if (!preg_match('/^[a-zA-Z0-9_\-]+$/i', $token)) {
                    return 'API-ключ содержит недопустимые символы. ' .
                           'API-ключ Яндекс.Маркет обычно состоит из букв, цифр, дефисов и подчеркиваний.';
                }
                break;
        }

        return null;
    }

    /**
     * Проверка валидности base64 токена
     */
    protected function isValidBase64Token(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        // Токен должен быть достаточно длинным (минимум 20 символов)
        if (strlen($token) < 20) {
            return false;
        }

        // Проверяем что токен содержит base64-подобные символы
        // Также разрешаем точку (.) для JWT токенов формата header.payload.signature
        if (!preg_match('/^[A-Za-z0-9+\/=_.-]+$/', $token)) {
            return false;
        }

        return true;
    }

    /**
     * Тестирование подключения к API маркетплейса
     */
    protected function testConnection(MarketplaceAccount $account): array
    {
        try {
            switch ($account->marketplace) {
                case 'wb':
                    return $this->testWildberriesConnection($account);

                case 'uzum':
                    return $this->testUzumConnection($account);

                case 'ozon':
                    return $this->testOzonConnection($account);

                case 'ym':
                    return $this->testYandexMarketConnection($account);

                default:
                    return [
                        'success' => true,
                        'message' => 'Тестирование для этого маркетплейса не реализовано.'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка при тестировании: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Тестирование подключения к Wildberries API
     */
    protected function testWildberriesConnection(MarketplaceAccount $account): array
    {
        try {
            $httpClient = new \App\Services\Marketplaces\Wildberries\WildberriesHttpClient($account);
            $orderService = new \App\Services\Marketplaces\Wildberries\WildberriesOrderService($httpClient);

            // Пробуем получить список поставок (лёгкий запрос)
            $result = $orderService->getSupplies($account, 1, 0);

            return [
                'success' => true,
                'message' => 'Подключение к Wildberries API успешно проверено.',
                'details' => 'Найдено поставок: ' . count($result['supplies'] ?? [])
            ];
        } catch (\Exception $e) {
            // Анализируем ошибку и возвращаем понятное сообщение
            $errorMessage = $e->getMessage();
            $userMessage = 'Не удалось подключиться к Wildberries API.';

            if (str_contains($errorMessage, '401') || str_contains($errorMessage, 'Unauthorized')) {
                $userMessage = 'API токен Wildberries недействителен или истёк. Получите новый токен в личном кабинете WB.';
            } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'Forbidden')) {
                $userMessage = 'API токен не имеет необходимых прав доступа. Проверьте права токена в личном кабинете WB.';
            } elseif (str_contains($errorMessage, '429') || str_contains($errorMessage, 'Too Many Requests')) {
                $userMessage = 'Превышен лимит запросов к API Wildberries. Попробуйте через несколько минут.';
            } elseif (str_contains($errorMessage, 'cURL error') || str_contains($errorMessage, 'Connection')) {
                $userMessage = 'Не удалось подключиться к серверу Wildberries. Проверьте подключение к интернету.';
            }

            return [
                'success' => false,
                'error' => $userMessage,
                'technical_details' => $errorMessage
            ];
        }
    }

    /**
     * Тестирование подключения к Yandex Market API
     */
    protected function testYandexMarketConnection(MarketplaceAccount $account): array
    {
        try {
            $httpClient = new \App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient();
            $client = new \App\Services\Marketplaces\YandexMarket\YandexMarketClient($httpClient);

            // Пробуем получить список кампаний
            $pingResult = $client->ping($account);

            if ($pingResult['success']) {
                $campaignsCount = $pingResult['campaigns_count'] ?? 0;
                $message = "Подключение к Yandex Market API успешно проверено. Найдено кампаний: {$campaignsCount}";

                if ($campaignsCount > 0) {
                    $campaigns = $pingResult['campaigns'] ?? [];
                    $campaignsList = implode(', ', array_map(fn($c) => $c['name'], $campaigns));
                    $message .= ". Кампании: {$campaignsList}";
                }

                return [
                    'success' => true,
                    'message' => $message,
                    'details' => $pingResult
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $pingResult['message'] ?? 'Неизвестная ошибка подключения'
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $userMessage = 'Не удалось подключиться к Yandex Market API.';

            if (str_contains($errorMessage, '401') || str_contains($errorMessage, 'Неверный API Key')) {
                $userMessage = 'API-ключ Яндекс.Маркет недействителен. Проверьте правильность ключа в личном кабинете: Настройки → API.';
            } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'Доступ запрещён')) {
                $userMessage = 'API-ключ не имеет необходимых прав доступа. Создайте новый ключ с правами на управление товарами и заказами.';
            } elseif (str_contains($errorMessage, '404') || str_contains($errorMessage, 'не найден')) {
                $userMessage = 'Указанная кампания (campaign_id) не найдена. Проверьте правильность ID кампании.';
            } elseif (str_contains($errorMessage, '429') || str_contains($errorMessage, 'лимит запросов')) {
                $userMessage = 'Превышен лимит запросов к API Яндекс.Маркет. Попробуйте через несколько минут.';
            } elseif (str_contains($errorMessage, 'cURL error') || str_contains($errorMessage, 'Connection') || str_contains($errorMessage, 'недоступен')) {
                $userMessage = 'Не удалось подключиться к серверу Яндекс.Маркет. Проверьте подключение к интернету.';
            }

            return [
                'success' => false,
                'error' => $userMessage,
                'technical_details' => $errorMessage
            ];
        }
    }

    /**
     * Тестирование подключения к Ozon API
     */
    protected function testOzonConnection(MarketplaceAccount $account): array
    {
        try {
            $httpClient = new \App\Services\Marketplaces\MarketplaceHttpClient();
            $client = new \App\Services\Marketplaces\OzonClient($httpClient);

            // Пробуем получить список складов
            $pingResult = $client->ping($account);

            if ($pingResult['success']) {
                $warehouses = $pingResult['data']['result'] ?? [];
                $warehouseCount = count($warehouses);
                $message = "Подключение к Ozon API успешно проверено.";

                if ($warehouseCount > 0) {
                    $warehouseNames = array_map(fn($w) => $w['name'] ?? 'Склад', $warehouses);
                    $warehousesList = implode(', ', array_slice($warehouseNames, 0, 3));
                    $message .= " Найдено складов: {$warehouseCount}. Склады: {$warehousesList}";
                    if ($warehouseCount > 3) {
                        $message .= " и ещё " . ($warehouseCount - 3);
                    }
                }

                return [
                    'success' => true,
                    'message' => $message,
                    'details' => $pingResult
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $pingResult['message'] ?? 'Неизвестная ошибка подключения'
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $userMessage = 'Не удалось подключиться к Ozon API.';

            if (str_contains($errorMessage, '401') || str_contains($errorMessage, 'Unauthorized')) {
                $userMessage = 'Client ID или API-ключ Ozon недействительны. Проверьте правильность данных в личном кабинете: Настройки → API ключи.';
            } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'Forbidden')) {
                $userMessage = 'API-ключ не имеет необходимых прав доступа. Создайте новый ключ с полными правами в личном кабинете Ozon.';
            } elseif (str_contains($errorMessage, '404')) {
                $userMessage = 'Неверный Client ID. Проверьте правильность Client ID в личном кабинете Ozon.';
            } elseif (str_contains($errorMessage, '429') || str_contains($errorMessage, 'Too Many Requests')) {
                $userMessage = 'Превышен лимит запросов к API Ozon. Попробуйте через несколько минут.';
            } elseif (str_contains($errorMessage, 'cURL error') || str_contains($errorMessage, 'Connection')) {
                $userMessage = 'Не удалось подключиться к серверу Ozon. Проверьте подключение к интернету.';
            }

            return [
                'success' => false,
                'error' => $userMessage,
                'technical_details' => $errorMessage
            ];
        }
    }

    /**
     * Тестирование подключения к Uzum API
     */
    protected function testUzumConnection(MarketplaceAccount $account): array
    {
        try {
            $httpClient = new \App\Services\Marketplaces\MarketplaceHttpClient($account, 'uzum');
            $client = new \App\Services\Marketplaces\UzumClient($httpClient, app(\App\Services\Marketplaces\IssueDetectorService::class));

            // Пробуем получить информацию о магазинах
            $shopIds = $account->credentials['shop_ids'] ?? [];
            if (empty($shopIds)) {
                return [
                    'success' => false,
                    'error' => 'Не указаны ID магазинов (shop_ids).'
                ];
            }

            // Делаем тестовый запрос к каталогу одного магазина
            $testShopId = $shopIds[0];
            $result = $client->fetchCatalog($account, $testShopId, 1, 0);

            return [
                'success' => true,
                'message' => 'Подключение к Uzum API успешно проверено.',
                'details' => "Доступ к магазину {$testShopId} подтверждён. Товаров: " . count($result['products'] ?? [])
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $userMessage = 'Не удалось подключиться к Uzum API.';

            if (str_contains($errorMessage, '401') || str_contains($errorMessage, 'Unauthorized')) {
                $userMessage = 'API токен Uzum недействителен или истёк. Получите новый токен в личном кабинете Uzum.';
            } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'open-api-005') ||
                      str_contains($errorMessage, 'Shops ids is not available')) {
                $userMessage = 'API токен не имеет доступа к указанным магазинам. Проверьте права токена и ID магазинов в личном кабинете Uzum.';
            } elseif (str_contains($errorMessage, '429') || str_contains($errorMessage, 'Too Many Requests')) {
                $userMessage = 'Превышен лимит запросов к API Uzum. Попробуйте через несколько минут.';
            } elseif (str_contains($errorMessage, 'cURL error') || str_contains($errorMessage, 'Connection')) {
                $userMessage = 'Не удалось подключиться к серверу Uzum. Проверьте подключение к интернету.';
            }

            return [
                'success' => false,
                'error' => $userMessage,
                'technical_details' => $errorMessage
            ];
        }
    }
}
