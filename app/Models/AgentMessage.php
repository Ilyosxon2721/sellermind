<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'agent_task_run_id',
        'role',
        'content',
        'tool_name',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_TOOL = 'tool';

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentTaskRun::class, 'agent_task_run_id');
    }

    public function isToolCall(): bool
    {
        return $this->role === self::ROLE_TOOL && $this->tool_name !== null;
    }
}
