<?php

namespace App\Models\Risment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RismentApiToken extends Model
{
    protected $table = 'risment_api_tokens';

    protected $fillable = [
        'company_id',
        'name',
        'token',
        'scopes',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = ['token'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // no scopes = full access
        }

        return in_array($scope, $this->scopes, true);
    }

    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
