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
        // Официальные цены OpenAI (обновлено: март 2026)
        $rates = [
            // GPT-5 models (новые)
            'gpt-5.1' => ['input' => 0.005, 'output' => 0.015],
            'gpt-5.1-kpi' => ['input' => 0.005, 'output' => 0.015], // KPI использует gpt-5.1
            'gpt-5' => ['input' => 0.004, 'output' => 0.012],

            // GPT-4o models
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt4o-mini-kpi' => ['input' => 0.00015, 'output' => 0.0006],

            // Legacy naming (backwards compatibility)
            'gpt5-mini' => ['input' => 0.00015, 'output' => 0.0006],

            // Vision & Image
            'gpt-vision' => ['input' => 0.0025, 'output' => 0.01],
            'image-api' => ['image' => 0.04],
        ];

        // Убираем суффикс '-kpi' для поиска базовой модели
        $baseModel = str_replace('-kpi', '', $model);
        $rate = $rates[$model] ?? $rates[$baseModel] ?? ['input' => 0.001, 'output' => 0.002];

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
