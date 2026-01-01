<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsageLog extends Model
{
    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'model',
        'tokens_input',
        'tokens_output',
        'images_generated',
        'cost_estimated',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'images_generated' => 'integer',
            'cost_estimated' => 'decimal:6',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalTokens(): int
    {
        return $this->tokens_input + $this->tokens_output;
    }

    public static function log(
        int $companyId,
        int $userId,
        string $model,
        int $tokensInput = 0,
        int $tokensOutput = 0,
        int $imagesGenerated = 0,
        ?array $meta = null
    ): self {
        $cost = self::estimateCost($model, $tokensInput, $tokensOutput, $imagesGenerated);

        return self::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'model' => $model,
            'tokens_input' => $tokensInput,
            'tokens_output' => $tokensOutput,
            'images_generated' => $imagesGenerated,
            'cost_estimated' => $cost,
            'meta' => $meta,
        ]);
    }

    private static function estimateCost(string $model, int $tokensInput, int $tokensOutput, int $images): float
    {
        $rates = [
            'gpt5-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt5' => ['input' => 0.01, 'output' => 0.03],
            'gpt-vision' => ['input' => 0.01, 'output' => 0.03],
            'image-api' => ['image' => 0.04],
        ];

        $rate = $rates[$model] ?? ['input' => 0.001, 'output' => 0.002];

        $cost = 0;
        if (isset($rate['input'])) {
            $cost += ($tokensInput / 1000) * $rate['input'];
        }
        if (isset($rate['output'])) {
            $cost += ($tokensOutput / 1000) * $rate['output'];
        }
        if (isset($rate['image'])) {
            $cost += $images * $rate['image'];
        }

        return $cost;
    }
}
