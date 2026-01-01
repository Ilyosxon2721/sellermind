<?php
// file: app/Models/VpcSnapshot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpcSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vpc_session_id',
        'image_path',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VpcSession::class, 'vpc_session_id');
    }

    /**
     * Get the full URL to the snapshot image
     */
    public function getImageUrl(): string
    {
        // TODO: Implement proper storage URL generation
        return asset('storage/' . $this->image_path);
    }
}
