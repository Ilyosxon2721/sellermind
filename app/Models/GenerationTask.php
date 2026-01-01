<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationTask extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'status',
        'input_data',
        'output_data',
        'progress',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'input_data' => 'array',
            'output_data' => 'array',
            'progress' => 'integer',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function markAsDone(array $outputData = []): void
    {
        $this->update([
            'status' => 'done',
            'progress' => 100,
            'output_data' => $outputData,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
    }
}
