<?php
// file: app/Models/MarketplaceProductTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductTemplate extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'marketplace',
        'internal_category_id',
        'name',
        'title_template',
        'description_template',
        'attributes_config',
    ];

    protected function casts(): array
    {
        return [
            'attributes_config' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function internalCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'internal_category_id');
    }

    /**
     * Render title from template with provided data
     */
    public function renderTitle(array $data): string
    {
        return $this->renderTemplate($this->title_template ?? '', $data);
    }

    /**
     * Render description from template with provided data
     */
    public function renderDescription(array $data): string
    {
        return $this->renderTemplate($this->description_template ?? '', $data);
    }

    /**
     * Replace template placeholders with actual data
     */
    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }

        // Remove any unreplaced placeholders
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);

        return trim($template);
    }

    /**
     * Find best matching template for product
     */
    public static function findForProduct(
        string $marketplace,
        ?int $accountId = null,
        ?int $categoryId = null
    ): ?self {
        $query = self::where('marketplace', $marketplace);

        // Try to find most specific match first
        if ($accountId && $categoryId) {
            $template = $query->clone()
                ->where('marketplace_account_id', $accountId)
                ->where('internal_category_id', $categoryId)
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Try account-specific template
        if ($accountId) {
            $template = $query->clone()
                ->where('marketplace_account_id', $accountId)
                ->whereNull('internal_category_id')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Try category-specific template
        if ($categoryId) {
            $template = $query->clone()
                ->whereNull('marketplace_account_id')
                ->where('internal_category_id', $categoryId)
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fall back to generic marketplace template
        return $query
            ->whereNull('marketplace_account_id')
            ->whereNull('internal_category_id')
            ->first();
    }
}
