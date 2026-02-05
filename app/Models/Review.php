<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'marketplace_account_id',
        'marketplace',
        'external_review_id',
        'external_order_id',
        'customer_name',
        'rating',
        'review_text',
        'photos',
        'review_date',
        'response_text',
        'response_date',
        'responded_by',
        'is_ai_generated',
        'template_id',
        'status',
        'is_published',
        'sentiment',
        'keywords',
    ];

    protected $casts = [
        'rating' => 'integer',
        'review_date' => 'datetime',
        'response_date' => 'datetime',
        'is_ai_generated' => 'boolean',
        'is_published' => 'boolean',
        'photos' => 'array',
        'keywords' => 'array',
    ];

    /**
     * Get the company that owns the review.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the product that was reviewed.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketplace account.
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Get the user who responded.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Get the template used for response.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id');
    }

    /**
     * Check if review has response.
     */
    public function hasResponse(): bool
    {
        return ! empty($this->response_text);
    }

    /**
     * Check if review is positive.
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if review is negative.
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Determine sentiment from rating.
     */
    public function determineSentiment(): string
    {
        if ($this->rating >= 4) {
            return 'positive';
        } elseif ($this->rating <= 2) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Scope for pending reviews.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for responded reviews.
     */
    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    /**
     * Scope for reviews by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for reviews by sentiment.
     */
    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    /**
     * Scope for reviews without response.
     */
    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('response_text');
    }
}
