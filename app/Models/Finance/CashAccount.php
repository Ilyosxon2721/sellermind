<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    public const TYPE_CASH = 'cash';

    public const TYPE_BANK = 'bank';

    public const TYPE_CARD = 'card';

    public const TYPE_EWALLET = 'ewallet';

    public const TYPE_MARKETPLACE = 'marketplace';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'currency_code',
        'balance',
        'initial_balance',
        'bank_name',
        'account_number',
        'bik',
        'card_number',
        'marketplace_account_id',
        'marketplace',
        'is_default',
        'is_active',
        'sort_order',
        'description',
        'notes',
    ];

    protected $casts = [
        'balance' => 'float',
        'initial_balance' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeMarketplace($query, ?string $marketplace = null)
    {
        $query->where('type', self::TYPE_MARKETPLACE);
        if ($marketplace) {
            $query->where('marketplace', $marketplace);
        }

        return $query;
    }

    /**
     * Получить название типа счёта
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CASH => 'Касса',
            self::TYPE_BANK => 'Банковский счёт',
            self::TYPE_CARD => 'Карта',
            self::TYPE_EWALLET => 'Электронный кошелёк',
            self::TYPE_MARKETPLACE => 'Маркетплейс',
            self::TYPE_OTHER => 'Прочее',
            default => $this->type,
        };
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_CASH => 'Касса',
            self::TYPE_BANK => 'Банковский счёт',
            self::TYPE_CARD => 'Карта',
            self::TYPE_EWALLET => 'Электронный кошелёк',
            self::TYPE_MARKETPLACE => 'Маркетплейс',
            self::TYPE_OTHER => 'Прочее',
        ];
    }

    /**
     * Обновить баланс счёта
     */
    public function updateBalance(float $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    /**
     * Пересчитать баланс на основе движений
     */
    public function recalculateBalance(): float
    {
        // Проверяем какая связь используется
        if (method_exists($this, 'movements') && $this->movements()->exists()) {
            $movementsSum = $this->movements()
                ->where('status', 'confirmed')
                ->selectRaw("SUM(CASE WHEN type IN ('income', 'transfer_in') THEN amount ELSE -amount END) as total")
                ->value('total') ?? 0;

            $this->balance = $this->initial_balance + $movementsSum;
        } else {
            // Fallback на старую логику с transactions
            $income = $this->transactions()
                ->where('status', 'confirmed')
                ->whereIn('type', ['income', 'transfer_in'])
                ->sum('amount');

            $expense = $this->transactions()
                ->where('status', 'confirmed')
                ->whereIn('type', ['expense', 'transfer_out'])
                ->sum('amount');

            $this->balance = $this->initial_balance + $income - $expense;
        }

        $this->save();

        return $this->balance;
    }

    /**
     * Получить или создать счёт для маркетплейса
     */
    public static function getOrCreateForMarketplace(int $companyId, MarketplaceAccount $marketplaceAccount): self
    {
        return self::firstOrCreate(
            [
                'company_id' => $companyId,
                'marketplace_account_id' => $marketplaceAccount->id,
            ],
            [
                'name' => $marketplaceAccount->name.' (выплаты)',
                'type' => self::TYPE_MARKETPLACE,
                'marketplace' => $marketplaceAccount->marketplace,
                'currency_code' => $marketplaceAccount->marketplace === 'uzum' ? 'UZS' : 'RUB',
                'balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Получить счёт по умолчанию для компании
     */
    public static function getDefaultForCompany(int $companyId, string $currencyCode = 'UZS'): ?self
    {
        return self::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('currency_code', $currencyCode)
            ->where('is_default', true)
            ->first()
            ?? self::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('currency_code', $currencyCode)
                ->first();
    }
}
