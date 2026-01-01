<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'agent_id',
        'product_id',
        'title',
        'description',
        'type',
        'input_payload',
        'status',
    ];

    protected $casts = [
        'input_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AgentTaskRun::class);
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(AgentTaskRun::class)->latestOfMany();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
