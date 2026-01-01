<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property array|null $allowed_values
 * @property string|null $unit
 * @property bool $is_variant_level
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductAttributeValue> $values
 */
class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'allowed_values',
        'unit',
        'is_variant_level',
    ];

    protected function casts(): array
    {
        return [
            'allowed_values' => 'array',
            'is_variant_level' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
