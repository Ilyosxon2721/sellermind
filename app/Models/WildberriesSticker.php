<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WildberriesSticker extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'batch_id',
        'order_ids',
        'file_path',
        'format',
        'file_size',
        'type',
        'width',
        'height',
        'is_printed',
        'printed_at',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
    ];

    /**
     * Get the marketplace account that owns the sticker
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Check if sticker file exists
     */
    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Get sticker file URL
     */
    public function getFileUrl(): ?string
    {
        if (!$this->fileExists()) {
            return null;
        }

        return Storage::disk('local')->url($this->file_path);
    }

    /**
     * Get sticker file content
     */
    public function getFileContent(): ?string
    {
        if (!$this->fileExists()) {
            return null;
        }

        return Storage::disk('local')->get($this->file_path);
    }

    /**
     * Delete sticker file
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk('local')->delete($this->file_path);
        }

        return false;
    }

    /**
     * Mark as printed
     */
    public function markAsPrinted(): bool
    {
        return $this->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);
    }

    /**
     * Get number of orders in this sticker
     */
    public function getOrdersCount(): int
    {
        return count($this->order_ids);
    }

    /**
     * Scope for printed stickers
     */
    public function scopePrinted($query)
    {
        return $query->where('is_printed', true);
    }

    /**
     * Scope for unprinted stickers
     */
    public function scopeUnprinted($query)
    {
        return $query->where('is_printed', false);
    }

    /**
     * Scope for standard stickers
     */
    public function scopeStandard($query)
    {
        return $query->where('type', 'standard');
    }

    /**
     * Scope for cross-border stickers
     */
    public function scopeCrossBorder($query)
    {
        return $query->where('type', 'cross-border');
    }

    /**
     * Scope by batch
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }
}
