<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dialog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'category',
        'is_private',
        'hidden_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'hidden_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // По умолчанию скрываем приватные диалоги которые были "удалены"
        static::addGlobalScope('visible', function ($query) {
            $query->whereNull('hidden_at');
        });
    }

    public function scopeWithHidden($query)
    {
        return $query->withoutGlobalScope('visible');
    }

    public function scopeOnlyHidden($query)
    {
        return $query->withoutGlobalScope('visible')->whereNotNull('hidden_at');
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function hide(): void
    {
        $this->update(['hidden_at' => now()]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage(): ?Message
    {
        return $this->messages()->latest()->first();
    }

    public function getContext(int $limit = 20): array
    {
        return $this->messages()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => $m->sender === 'user' ? 'user' : 'assistant',
                'content' => $m->content,
            ])
            ->values()
            ->toArray();
    }
}
