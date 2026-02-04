<?php

// file: app/Models/VpcSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpcSession extends Model
{
    // Статусы жизненного цикла
    const STATUS_CREATING = 'creating';

    const STATUS_READY = 'ready';

    const STATUS_RUNNING = 'running';

    const STATUS_PAUSED = 'paused';

    const STATUS_STOPPED = 'stopped';

    const STATUS_ERROR = 'error';

    // Режимы управления
    const CONTROL_AGENT = 'AGENT_CONTROL';

    const CONTROL_USER = 'USER_CONTROL';

    const CONTROL_PAUSED = 'PAUSED';

    protected $fillable = [
        'user_id',
        'company_id',
        'agent_task_id',
        'name',
        'status',
        'control_mode',
        'endpoint',
        'display_token',
        'started_at',
        'stopped_at',
        'last_activity_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agentTask(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(VpcAction::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(VpcSnapshot::class);
    }

    // Helpers
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isStopped(): bool
    {
        return $this->status === self::STATUS_STOPPED;
    }

    public function isAgentControlled(): bool
    {
        return $this->control_mode === self::CONTROL_AGENT;
    }

    public function isUserControlled(): bool
    {
        return $this->control_mode === self::CONTROL_USER;
    }

    public function isPaused(): bool
    {
        return $this->control_mode === self::CONTROL_PAUSED;
    }
}
