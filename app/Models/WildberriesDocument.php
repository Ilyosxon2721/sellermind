<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WildberriesDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'document_id',
        'document_number',
        'category',
        'type',
        'format',
        'document_date',
        'amount',
        'currency',
        'file_path',
        'file_size',
        'status',
        'is_downloaded',
        'raw_data',
    ];

    protected $casts = [
        'document_date' => 'date',
        'amount' => 'decimal:2',
        'is_downloaded' => 'boolean',
        'raw_data' => 'array',
    ];

    /**
     * Get the marketplace account that owns the document
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Check if document file exists
     */
    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Get document file URL
     */
    public function getFileUrl(): ?string
    {
        if (!$this->fileExists()) {
            return null;
        }

        return Storage::disk('local')->url($this->file_path);
    }

    /**
     * Get document file content
     */
    public function getFileContent(): ?string
    {
        if (!$this->fileExists()) {
            return null;
        }

        return Storage::disk('local')->get($this->file_path);
    }

    /**
     * Delete document file
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk('local')->delete($this->file_path);
        }

        return false;
    }

    /**
     * Scope for downloaded documents
     */
    public function scopeDownloaded($query)
    {
        return $query->where('is_downloaded', true);
    }

    /**
     * Scope for active documents
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
