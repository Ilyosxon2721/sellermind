<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalOption extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(GlobalOptionValue::class)->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->values()->where('is_active', true);
    }

    /**
     * Получить глобальную опцию размеров (без привязки к компании)
     */
    public static function sizes(?int $companyId = null)
    {
        return static::whereNull('company_id')
            ->where('code', 'size')
            ->first();
    }

    /**
     * Получить глобальную опцию цветов (без привязки к компании)
     */
    public static function colors(?int $companyId = null)
    {
        return static::whereNull('company_id')
            ->where('code', 'color')
            ->first();
    }

    /**
     * Получить все значения размеров (глобальные + компании)
     */
    public static function sizesWithCompany(?int $companyId = null)
    {
        $option = static::sizes();
        if (! $option) {
            return collect();
        }

        return GlobalOptionValue::where('global_option_id', $option->id)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Получить все значения цветов (глобальные + компании)
     */
    public static function colorsWithCompany(?int $companyId = null)
    {
        $option = static::colors();
        if (! $option) {
            return collect();
        }

        return GlobalOptionValue::where('global_option_id', $option->id)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('sort_order')
            ->get();
    }
}
