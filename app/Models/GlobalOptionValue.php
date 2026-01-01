<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalOptionValue extends Model
{
    protected $fillable = [
        'company_id',
        'global_option_id',
        'value',
        'code',
        'color_hex',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(GlobalOption::class, 'global_option_id');
    }
}
