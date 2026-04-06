<?php

declare(strict_types=1);

namespace App\Models\Risment;

use App\Models\Company;
use App\Models\IntegrationLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Клиент фулфилмента в системе RISMENT.
 * Каждый клиент имеет свой аккаунт RISMENT, свои токены и вебхуки.
 */
final class RismentClient extends Model
{
    protected $table = 'risment_clients';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'contact_person',
        'contact_phone',
        'contact_email',
        'risment_account_id',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(RismentApiToken::class, 'risment_client_id');
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(RismentWebhookEndpoint::class, 'risment_client_id');
    }

    public function integrationLinks(): HasMany
    {
        return $this->hasMany(IntegrationLink::class, 'risment_client_id');
    }

    public function activeLink(): HasOne
    {
        return $this->hasOne(IntegrationLink::class, 'risment_client_id')
            ->where('is_active', true)
            ->where('external_system', 'risment');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
