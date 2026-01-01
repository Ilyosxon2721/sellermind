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

    public static function sizes(int $companyId)
    {
        return static::where('company_id', $companyId)
            ->where('code', 'size')
            ->first();
    }

    public static function colors(int $companyId)
    {
        return static::where('company_id', $companyId)
            ->where('code', 'color')
            ->first();
    }
}
