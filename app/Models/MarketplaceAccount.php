<?php
// file: app/Models/MarketplaceAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class MarketplaceAccount extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'marketplace',
        'name',
        'api_key',
        'client_id',
        'client_secret',
        'oauth_token',
        'oauth_refresh_token',
        'shop_id',
        // Uzum specific
        'uzum_client_id',
        'uzum_client_secret',
        'uzum_api_key',
        'uzum_refresh_token',
        'uzum_access_token',
        'uzum_token_expires_at',
        'uzum_settings',
        'credentials',
        'credentials_json',
        'is_active',
        'connected_at',
        // Wildberries specific tokens
        'wb_content_token',
        'wb_marketplace_token',
        'wb_prices_token',
        'wb_statistics_token',
        'wb_tokens_valid',
        'wb_last_successful_call',
        'stock_sync_strategy',
        'stock_size_strategy',
        'sync_settings',
    ];

    // Fields that should be encrypted
    protected static array $encryptedFields = [
        'api_key',
        'client_secret',
        'oauth_token',
        'oauth_refresh_token',
        'wb_content_token',
        'wb_marketplace_token',
        'wb_prices_token',
        'wb_statistics_token',
        'uzum_client_secret',
        'uzum_api_key',
        'uzum_refresh_token',
        'uzum_access_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'connected_at' => 'datetime',
            'credentials_json' => 'array',
            'wb_tokens_valid' => 'boolean',
            'wb_last_successful_call' => 'datetime',
            'uzum_token_expires_at' => 'datetime',
            'uzum_settings' => 'array',
            'stock_sync_strategy' => 'string',
            'stock_size_strategy' => 'string',
            'sync_settings' => 'array',
        ];
    }

    protected $hidden = [
        'credentials',
        'api_key',
        'client_secret',
        'oauth_token',
        'oauth_refresh_token',
        'wb_content_token',
        'wb_marketplace_token',
        'wb_prices_token',
        'wb_statistics_token',
        'uzum_client_secret',
        'uzum_api_key',
        'uzum_refresh_token',
        'uzum_access_token',
    ];

    /**
     * Boot model events for auditing
     */
    protected static function booted(): void
    {
        static::created(function (MarketplaceAccount $account) {
            MarketplaceAccountAudit::log(
                $account->id,
                MarketplaceAccountAudit::EVENT_CREATED,
                null,
                MarketplaceAccountAudit::maskSensitiveValues($account->getAttributes()),
                "Создан аккаунт {$account->marketplace}"
            );
        });

        static::updating(function (MarketplaceAccount $account) {
            $account->_originalForAudit = $account->getOriginal();
        });

        static::updated(function (MarketplaceAccount $account) {
            $original = $account->_originalForAudit ?? [];
            $changes = $account->getChanges();

            if (empty($changes)) {
                return;
            }

            // Check if credentials were changed
            $credentialFields = array_intersect(
                array_keys($changes),
                self::$encryptedFields
            );

            if (!empty($credentialFields)) {
                MarketplaceAccountAudit::logCredentialsChange($account->id, $credentialFields);
            }

            // Check for activation/deactivation
            if (isset($changes['is_active'])) {
                $event = $changes['is_active']
                    ? MarketplaceAccountAudit::EVENT_ACTIVATED
                    : MarketplaceAccountAudit::EVENT_DEACTIVATED;

                MarketplaceAccountAudit::log(
                    $account->id,
                    $event,
                    null,
                    null,
                    $changes['is_active'] ? 'Аккаунт активирован' : 'Аккаунт деактивирован'
                );
            }

            // Log other changes
            $nonCredentialChanges = array_diff_key(
                $changes,
                array_flip(self::$encryptedFields),
                ['is_active' => true, 'updated_at' => true]
            );

            if (!empty($nonCredentialChanges)) {
                MarketplaceAccountAudit::log(
                    $account->id,
                    MarketplaceAccountAudit::EVENT_UPDATED,
                    MarketplaceAccountAudit::maskSensitiveValues(
                        array_intersect_key($original, $nonCredentialChanges)
                    ),
                    MarketplaceAccountAudit::maskSensitiveValues($nonCredentialChanges),
                    'Обновлены данные аккаунта'
                );
            }
        });

        static::deleting(function (MarketplaceAccount $account) {
            MarketplaceAccountAudit::log(
                $account->id,
                MarketplaceAccountAudit::EVENT_DELETED,
                MarketplaceAccountAudit::maskSensitiveValues($account->getAttributes()),
                null,
                "Удалён аккаунт {$account->marketplace}"
            );
        });
    }

    // Temporary storage for original values during update
    protected $_originalForAudit = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function orders(): HasMany
    {
        // Используем специфичные таблицы для каждого маркетплейса
        if ($this->marketplace === 'wb') {
            return $this->hasMany(\App\Models\WbOrder::class);
        }

        if ($this->marketplace === 'uzum') {
            return $this->hasMany(\App\Models\UzumOrder::class);
        }

        if ($this->marketplace === 'ozon') {
            return $this->hasMany(\App\Models\OzonOrder::class);
        }

        if ($this->marketplace === 'ym' || $this->marketplace === 'yandex_market') {
            return $this->hasMany(\App\Models\YandexMarketOrder::class);
        }

        // Fallback - return empty relation (will return 0 on count)
        return $this->hasMany(\App\Models\WbOrder::class)->whereRaw('1=0');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MarketplaceAccountAudit::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(MarketplacePayout::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(MarketplaceReturn::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(MarketplaceStock::class);
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(MarketplaceAutomationRule::class);
    }

    public function syncSchedules(): HasMany
    {
        return $this->hasMany(MarketplaceSyncSchedule::class);
    }

    // ========== Encryption Accessors ==========

    /**
     * Set encrypted api_key
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted api_key
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setUzumApiKeyAttribute(?string $value): void
    {
        $this->attributes['uzum_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getUzumApiKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setUzumAccessTokenAttribute(?string $value): void
    {
        $this->attributes['uzum_access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getUzumAccessTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setUzumRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['uzum_refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getUzumRefreshTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setUzumClientSecretAttribute(?string $value): void
    {
        $this->attributes['uzum_client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getUzumClientSecretAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted client_secret
     */
    public function setClientSecretAttribute(?string $value): void
    {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted client_secret
     */
    public function getClientSecretAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted oauth_token
     */
    public function setOauthTokenAttribute(?string $value): void
    {
        $this->attributes['oauth_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted oauth_token
     */
    public function getOauthTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted oauth_refresh_token
     */
    public function setOauthRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['oauth_refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted oauth_refresh_token
     */
    public function getOauthRefreshTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted credentials (legacy format)
     */
    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Get decrypted credentials (legacy format)
     */
    public function getDecryptedCredentials(): array
    {
        if (empty($this->attributes['credentials'])) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($this->attributes['credentials']), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Log sync event to audit
     */
    public function logSync(string $syncType, ?string $description = null): void
    {
        MarketplaceAccountAudit::log(
            $this->id,
            MarketplaceAccountAudit::EVENT_SYNCED,
            null,
            ['sync_type' => $syncType],
            $description ?? "Синхронизация: {$syncType}"
        );
    }

    /**
     * Log error to audit
     */
    public function logError(string $error, ?array $context = null): void
    {
        MarketplaceAccountAudit::log(
            $this->id,
            MarketplaceAccountAudit::EVENT_ERROR,
            null,
            $context,
            $error
        );
    }

    /**
     * Get all credentials as array (combines new fields with legacy)
     */
    public function getAllCredentials(): array
    {
        $credentials = [
            'api_key' => $this->api_key,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'oauth_token' => $this->oauth_token,
            'oauth_refresh_token' => $this->oauth_refresh_token,
            'shop_id' => $this->shop_id,
            // Uzum specific
            'uzum_client_id' => $this->uzum_client_id,
            'uzum_client_secret' => $this->uzum_client_secret,
            'uzum_api_key' => $this->uzum_api_key,
            'uzum_access_token' => $this->uzum_access_token,
            'uzum_refresh_token' => $this->uzum_refresh_token,
            'uzum_token_expires_at' => $this->uzum_token_expires_at,
            'uzum_settings' => $this->uzum_settings,
            // Wildberries category-specific tokens
            'wb_content_token' => $this->wb_content_token,
            'wb_marketplace_token' => $this->wb_marketplace_token,
            'wb_prices_token' => $this->wb_prices_token,
            'wb_statistics_token' => $this->wb_statistics_token,
        ];

        // Merge with credentials_json
        if ($this->credentials_json) {
            $credentials = array_merge($credentials, $this->credentials_json);
        }

        // Merge with legacy credentials
        $legacy = $this->getDecryptedCredentials();
        if ($legacy) {
            $credentials = array_merge($credentials, $legacy);
        }

        return array_filter($credentials);
    }

    public function markAsConnected(): void
    {
        $this->update([
            'is_active' => true,
            'connected_at' => now(),
        ]);
    }

    public function disconnect(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get display name for account
     */
    public function getDisplayName(): string
    {
        return $this->name ?? self::getMarketplaceLabels()[$this->marketplace] ?? $this->marketplace;
    }

    public static function getMarketplaceLabels(): array
    {
        return [
            'uzum' => 'Uzum Market',
            'wb' => 'Wildberries',
            'ozon' => 'Ozon',
            'ym' => 'Yandex Market',
        ];
    }

    public static function getMarketplaceCodes(): array
    {
        return ['uzum', 'wb', 'ozon', 'ym'];
    }

    // ========== Sync Settings Methods ==========

    /**
     * Настройки синхронизации по умолчанию
     */
    public static array $defaultSyncSettings = [
        'auto_sync_stock_on_link' => true,      // Автосинхронизация при привязке товара
        'auto_sync_stock_on_change' => true,    // Автосинхронизация при изменении остатков
        'stock_sync_enabled' => true,           // Общий выключатель синхронизации остатков
    ];

    /**
     * Получить значение настройки синхронизации
     */
    public function getSyncSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->sync_settings ?? [];
        return $settings[$key] ?? self::$defaultSyncSettings[$key] ?? $default;
    }

    /**
     * Установить значение настройки синхронизации
     */
    public function setSyncSetting(string $key, mixed $value): void
    {
        $settings = $this->sync_settings ?? [];
        $settings[$key] = $value;
        $this->sync_settings = $settings;
    }

    /**
     * Получить все настройки синхронизации с дефолтами
     */
    public function getAllSyncSettings(): array
    {
        return array_merge(self::$defaultSyncSettings, $this->sync_settings ?? []);
    }

    /**
     * Проверить, включена ли автосинхронизация при привязке
     */
    public function isAutoSyncOnLinkEnabled(): bool
    {
        return $this->getSyncSetting('stock_sync_enabled', true)
            && $this->getSyncSetting('auto_sync_stock_on_link', true);
    }

    /**
     * Проверить, включена ли автосинхронизация при изменении остатков
     */
    public function isAutoSyncOnChangeEnabled(): bool
    {
        return $this->getSyncSetting('stock_sync_enabled', true)
            && $this->getSyncSetting('auto_sync_stock_on_change', true);
    }

    // ========== Wildberries Specific Methods ==========

    /**
     * Check if this account is Wildberries
     */
    public function isWildberries(): bool
    {
        $code = strtolower((string) $this->marketplace);
        return $code === 'wb' || $code === 'wildberries';
    }

    public function isUzum(): bool
    {
        return strtolower((string) $this->marketplace) === 'uzum';
    }

    /**
     * Check if Uzum credentials present
     */
    public function isUzumConfigured(): bool
    {
        if (!$this->isUzum()) {
            return false;
        }

        return (bool) ($this->uzum_access_token || $this->uzum_api_key || $this->api_key);
    }

    /**
     * Build Uzum auth headers from stored credentials
     */
    public function getUzumAuthHeaders(): array
    {
        $token = $this->uzum_access_token ?? $this->uzum_api_key ?? $this->api_key;
        if (!$token) {
            return [];
        }

        // Токены в БД могут быть зашифрованы через Crypt::encryptString
        // Пробуем расшифровать, а при ошибке используем исходное значение.
        try {
            $token = decrypt($token);
        } catch (\Throwable $e) {
            // оставляем как есть
        }

        $header = config('uzum.auth.header', 'Authorization');
        // По спецификации Uzum токен передается без Bearer-префикса,
        // но оставляем поддержку кастомного префикса через конфиг.
        $prefix = trim(config('uzum.auth.prefix', ''));
        $value = $prefix !== '' ? trim($prefix . ' ' . $token) : $token;

        // Отдаем сразу оба заголовка: Authorization и X-API-KEY,
        // чтобы исключить проблемы с ожиданиями на стороне API.
        return [
            $header => $value,
            'X-API-KEY' => $value,
        ];
    }

    /**
     * Get Uzum settings value with dot-notation support
     */
    public function getUzumSettings(string $key, $default = null)
    {
        return data_get($this->uzum_settings ?? [], $key, $default);
    }

    public function shops()
    {
        return $this->hasMany(MarketplaceShop::class, 'marketplace_account_id');
    }

    /**
     * Get WB token for specific API category
     *
     * @param string $category content|marketplace|prices|statistics
     */
    public function getWbToken(string $category): ?string
    {
        // First try direct column attributes
        $token = match ($category) {
            'content'     => $this->wb_content_token,
            'marketplace' => $this->wb_marketplace_token,
            'prices'      => $this->wb_prices_token,
            'statistics'  => $this->wb_statistics_token,
            default       => null,
        };

        // Fallback to credentials JSON if column is empty
        if (!$token) {
            $credentials = $this->getDecryptedCredentials();
            $credKey = match ($category) {
                'content'     => 'wb_content_token',
                'marketplace' => 'wb_marketplace_token',
                'prices'      => 'wb_prices_token',
                'statistics'  => 'wb_statistics_token',
                default       => null,
            };
            $token = $credKey ? ($credentials[$credKey] ?? null) : null;
        }

        // Final fallback to api_key/api_token
        if (!$token) {
            $credentials = $this->getDecryptedCredentials();
            $token = $this->api_key ?: ($credentials['api_key'] ?? $credentials['api_token'] ?? null);
        }

        return $token;
    }

    /**
     * Set WB token for specific API category
     */
    public function setWbToken(string $category, ?string $token): void
    {
        $field = match ($category) {
            'content'     => 'wb_content_token',
            'marketplace' => 'wb_marketplace_token',
            'prices'      => 'wb_prices_token',
            'statistics'  => 'wb_statistics_token',
            default       => null,
        };

        if ($field) {
            $this->{$field} = $token;
        }
    }

    /**
     * Mark WB tokens as invalid (e.g., after auth error)
     */
    public function markWbTokensInvalid(): void
    {
        $this->update(['wb_tokens_valid' => false]);
    }

    /**
     * Mark WB tokens as valid and update last successful call
     */
    public function markWbTokensValid(): void
    {
        $this->update([
            'wb_tokens_valid' => true,
            'wb_last_successful_call' => now(),
        ]);
    }

    // ========== WB Token Encryption Accessors ==========

    public function setWbContentTokenAttribute(?string $value): void
    {
        $this->attributes['wb_content_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWbContentTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setWbMarketplaceTokenAttribute(?string $value): void
    {
        $this->attributes['wb_marketplace_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWbMarketplaceTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setWbPricesTokenAttribute(?string $value): void
    {
        $this->attributes['wb_prices_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWbPricesTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setWbStatisticsTokenAttribute(?string $value): void
    {
        $this->attributes['wb_statistics_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWbStatisticsTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // ========== WB Relationships ==========

    public function wildberriesProducts(): HasMany
    {
        return $this->hasMany(WildberriesProduct::class, 'marketplace_account_id');
    }

    public function wildberriesOrders(): HasMany
    {
        return $this->hasMany(WildberriesOrder::class, 'marketplace_account_id');
    }

    public function wildberriesWarehouses(): HasMany
    {
        return $this->hasMany(WildberriesWarehouse::class, 'marketplace_account_id');
    }

    public function marketplaceWarehouses(): HasMany
    {
        return $this->hasMany(MarketplaceWarehouse::class, 'marketplace_account_id');
    }

    public function wildberriesPasses(): HasMany
    {
        return $this->hasMany(WildberriesPass::class, 'marketplace_account_id');
    }

    public function wildberriesSupplies(): HasMany
    {
        return $this->hasMany(WildberriesSupply::class, 'marketplace_account_id');
    }

    public function wildberriesDocuments(): HasMany
    {
        return $this->hasMany(WildberriesDocument::class, 'marketplace_account_id');
    }

    public function wildberriesStickers(): HasMany
    {
        return $this->hasMany(WildberriesSticker::class, 'marketplace_account_id');
    }
}
