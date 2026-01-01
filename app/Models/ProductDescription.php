<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDescription extends Model
{
    protected $fillable = [
        'product_id',
        'marketplace',
        'language',
        'title',
        'short_description',
        'full_description',
        'bullets',
        'attributes',
        'keywords',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'attributes' => 'array',
            'keywords' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function createNewVersion(Product $product, string $marketplace, string $language, array $data): self
    {
        $currentVersion = self::where('product_id', $product->id)
            ->where('marketplace', $marketplace)
            ->where('language', $language)
            ->max('version') ?? 0;

        return self::create([
            'product_id' => $product->id,
            'marketplace' => $marketplace,
            'language' => $language,
            'version' => $currentVersion + 1,
            ...$data,
        ]);
    }
}
