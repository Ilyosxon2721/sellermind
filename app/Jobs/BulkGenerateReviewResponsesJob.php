<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\ReviewResponseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkGenerateReviewResponsesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $reviewIds;
    public array $options;
    public bool $saveImmediately;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(array $reviewIds, array $options = [], bool $saveImmediately = false)
    {
        $this->reviewIds = $reviewIds;
        $this->options = $options;
        $this->saveImmediately = $saveImmediately;
    }

    /**
     * Execute the job.
     */
    public function handle(ReviewResponseService $reviewResponseService): void
    {
        try {
            Log::info("Starting bulk review response generation for " . count($this->reviewIds) . " reviews");

            $successCount = 0;
            $failedCount = 0;

            foreach ($this->reviewIds as $reviewId) {
                try {
                    $review = Review::find($reviewId);

                    if (!$review) {
                        Log::warning("Review {$reviewId} not found");
                        $failedCount++;
                        continue;
                    }

                    // Skip if already has response
                    if ($review->hasResponse()) {
                        Log::info("Review {$reviewId} already has response, skipping");
                        continue;
                    }

                    // Generate response
                    $response = $reviewResponseService->generateResponse($review, $this->options);

                    if ($this->saveImmediately) {
                        $review->update([
                            'response_text' => $response,
                            'is_ai_generated' => true,
                            'status' => 'responded',
                            'responded_at' => now(),
                        ]);

                        Log::info("Generated and saved response for review {$reviewId}");
                    } else {
                        // Just generate, don't save (for manual review)
                        Log::info("Generated response for review {$reviewId} (not saved)");
                    }

                    $successCount++;

                    // Rate limiting to avoid overwhelming AI API
                    if ($successCount % 10 === 0) {
                        sleep(1);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to generate response for review {$reviewId}: " . $e->getMessage());
                    $failedCount++;
                }
            }

            Log::info("Bulk review response generation completed: {$successCount} succeeded, {$failedCount} failed");
        } catch (\Exception $e) {
            Log::error("Bulk review response generation job failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("BulkGenerateReviewResponsesJob failed: " . $exception->getMessage());
    }
}
