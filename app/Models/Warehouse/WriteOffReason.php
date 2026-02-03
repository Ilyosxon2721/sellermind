<?php

namespace App\Models\Warehouse;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WriteOffReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_default',
        'is_active',
        'requires_comment',
        'affects_cost',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'requires_comment' => 'boolean',
        'affects_cost' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get default reasons for seeding
     */
    public static function getDefaultReasons(): array
    {
        return [
            [
                'code' => 'damaged',
                'name' => 'Брак/повреждение',
                'description' => 'Товар повреждён или имеет дефекты',
                'is_default' => true,
                'requires_comment' => false,
                'affects_cost' => true,
            ],
            [
                'code' => 'expired',
                'name' => 'Истёк срок годности',
                'description' => 'Срок годности товара истёк',
                'is_default' => false,
                'requires_comment' => false,
                'affects_cost' => true,
            ],
            [
                'code' => 'lost',
                'name' => 'Утеря/недостача',
                'description' => 'Товар утерян или обнаружена недостача',
                'is_default' => false,
                'requires_comment' => true,
                'affects_cost' => true,
            ],
            [
                'code' => 'sample',
                'name' => 'Образец/подарок',
                'description' => 'Товар использован как образец или подарок',
                'is_default' => false,
                'requires_comment' => false,
                'affects_cost' => false,
            ],
            [
                'code' => 'theft',
                'name' => 'Кража',
                'description' => 'Товар украден',
                'is_default' => false,
                'requires_comment' => true,
                'affects_cost' => true,
            ],
            [
                'code' => 'natural_loss',
                'name' => 'Естественная убыль',
                'description' => 'Естественная убыль товара при хранении',
                'is_default' => false,
                'requires_comment' => false,
                'affects_cost' => true,
            ],
            [
                'code' => 'production',
                'name' => 'Использовано в производстве',
                'description' => 'Товар использован в производственном процессе',
                'is_default' => false,
                'requires_comment' => false,
                'affects_cost' => false,
            ],
            [
                'code' => 'other',
                'name' => 'Прочее',
                'description' => 'Другая причина списания',
                'is_default' => false,
                'requires_comment' => true,
                'affects_cost' => true,
            ],
        ];
    }
}
