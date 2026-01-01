<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductChannelSetting> $productSettings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductChannelVariantSetting> $variantSettings
 */
class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function productSettings(): HasMany
    {
        return $this->hasMany(ProductChannelSetting::class);
    }

    public function variantSettings(): HasMany
    {
        return $this->hasMany(ProductChannelVariantSetting::class);
    }
}
