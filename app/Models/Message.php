<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'dialog_id',
        'sender',
        'content',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->meta['attachments'] ?? []);
    }

    public function getAttachments(): array
    {
        return $this->meta['attachments'] ?? [];
    }

    public function hasImages(): bool
    {
        return !empty($this->meta['images'] ?? []);
    }

    public function getImages(): array
    {
        return $this->meta['images'] ?? [];
    }
}
