<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'template_text',
        'category',
        'rating_range',
        'keywords',
        'usage_count',
        'last_used_at',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'rating_range' => 'array',
        'keywords' => 'array',
        'last_used_at' => 'datetime',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the template.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get reviews that used this template.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'template_id');
    }

    /**
     * Check if template matches review criteria.
     */
    public function matchesReview(Review $review): bool
    {
        // Check rating range
        if ($this->rating_range) {
            [$min, $max] = $this->rating_range;
            if ($review->rating < $min || $review->rating > $max) {
                return false;
            }
        }

        // Check keywords
        if ($this->keywords && !empty($this->keywords)) {
            $reviewText = strtolower($review->review_text);
            foreach ($this->keywords as $keyword) {
                if (str_contains($reviewText, strtolower($keyword))) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Apply template to review text.
     */
    public function apply(array $variables = []): string
    {
        $text = $this->template_text;

        // Replace variables like {customer_name}, {product_name}
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope for system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
