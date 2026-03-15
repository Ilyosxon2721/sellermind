<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReplyLog extends Model
{
    protected $fillable = [
        'uzum_shop_id',
        'uzum_review_id',
        'rating',
        'review_text',
        'reply_text',
        'product_name',
        'status',
        'error_message',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];
}
