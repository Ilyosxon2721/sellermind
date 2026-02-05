<?php

// file: app/Models/MarketplaceAttributeMapping.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceAttributeMapping extends Model
{
    public const VALUE_MODE_SIMPLE = 'simple';

    public const VALUE_MODE_DICTIONARY = 'dictionary';

    public const VALUE_MODE_CUSTOM = 'custom';

    protected $fillable = [
        'marketplace',
        'internal_attribute_code',
        'external_attribute_id',
        'external_attribute_name',
        'value_mode',
        'value_mapping',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'value_mapping' => 'array',
            'extra' => 'array',
        ];
    }

    /**
     * Map internal value to external using configured mapping
     */
    public function mapValue(mixed $internalValue): mixed
    {
        if ($this->value_mode === self::VALUE_MODE_SIMPLE) {
            return $internalValue;
        }

        if ($this->value_mode === self::VALUE_MODE_DICTIONARY && $this->value_mapping) {
            return $this->value_mapping[$internalValue] ?? $internalValue;
        }

        return $internalValue;
    }

    /**
     * Find mapping by marketplace and internal attribute code
     */
    public static function findMapping(string $marketplace, string $internalAttributeCode): ?self
    {
        return self::where('marketplace', $marketplace)
            ->where('internal_attribute_code', $internalAttributeCode)
            ->first();
    }

    /**
     * Get all mappings for a marketplace
     */
    public static function getForMarketplace(string $marketplace): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('marketplace', $marketplace)->get();
    }
}
