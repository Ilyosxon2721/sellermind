<?php

// file: app/Models/VpcAction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpcAction extends Model
{
    public $timestamps = false;

    // Источники действий
    const SOURCE_AGENT = 'agent';

    const SOURCE_USER = 'user';

    // Типы действий
    const ACTION_OPEN_URL = 'open_url';

    const ACTION_CLICK = 'click';

    const ACTION_TYPE = 'type';

    const ACTION_SCROLL = 'scroll';

    const ACTION_SCREENSHOT = 'screenshot';

    const ACTION_KEY_PRESS = 'key_press';

    const ACTION_MOUSE_MOVE = 'mouse_move';

    protected $fillable = [
        'vpc_session_id',
        'source',
        'action_type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VpcSession::class, 'vpc_session_id');
    }

    public function isFromAgent(): bool
    {
        return $this->source === self::SOURCE_AGENT;
    }

    public function isFromUser(): bool
    {
        return $this->source === self::SOURCE_USER;
    }
}
