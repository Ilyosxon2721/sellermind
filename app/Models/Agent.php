<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'system_prompt',
        'model',
        'enabled_tools',
        'is_active',
    ];

    protected $casts = [
        'enabled_tools' => 'array',
        'is_active' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getEnabledToolNames(): array
    {
        return $this->enabled_tools ?? [];
    }
}
