<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель категории Uzum Market
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property int $products_count
 * @property \Carbon\Carbon|null $last_synced_at
 */
final class UzumCategory extends Model
{
    protected $table = 'uzum_categories';

    public $incrementing = false; // ID задаётся вручную из API Uzum

    protected $fillable = [
        'id',
        'parent_id',
        'title',
        'products_count',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'products_count' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Родительская категория
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Дочерние категории
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Снепшоты товаров в категории
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(UzumProductSnapshot::class, 'category_id');
    }

    /**
     * Является ли корневой категорией
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Полный путь категории (через родителей)
     */
    public function getPath(): string
    {
        if ($this->isRoot()) {
            return $this->title;
        }

        $path = [$this->title];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($path, $parent->title);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Scope для корневых категорий
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope для категорий с товарами
     */
    public function scopeWithProducts($query)
    {
        return $query->where('products_count', '>', 0);
    }
}
