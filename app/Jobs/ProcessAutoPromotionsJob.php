<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\PromotionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutoPromotionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $companyId;
    public array $criteria;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId, array $criteria = [])
    {
        $this->companyId = $companyId;
        $this->criteria = $criteria;
    }

    /**
     * Execute the job.
     */
    public function handle(PromotionService $promotionService): void
    {
        try {
            Log::info("Processing auto promotions for company {$this->companyId}");

            $company = Company::find($this->companyId);

            if (!$company || !$company->is_active) {
                Log::warning("Company {$this->companyId} not found or inactive");
                return;
            }

            // Detect slow-moving products
            $slowProducts = $promotionService->detectSlowMovingProducts($this->companyId, $this->criteria);

            Log::info("Found {$slowProducts->count()} slow-moving products for company {$this->companyId}");

            if ($slowProducts->isEmpty()) {
                Log::info("No slow-moving products found for company {$this->companyId}");
                return;
            }

            // Create automatic promotions
            $createdCount = 0;
            foreach ($slowProducts as $productData) {
                try {
                    $promotion = $promotionService->createAutomaticPromotion(
                        $this->companyId,
                        [$productData['variant_id']],
                        $productData['recommended_discount']
                    );

                    if ($promotion) {
                        $createdCount++;
                        Log::info("Created promotion {$promotion->id} for variant {$productData['variant_id']}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to create promotion for variant {$productData['variant_id']}: " . $e->getMessage());
                }
            }

            Log::info("Successfully created {$createdCount} automatic promotions for company {$this->companyId}");
        } catch (\Exception $e) {
            Log::error("Failed to process auto promotions for company {$this->companyId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessAutoPromotionsJob failed for company {$this->companyId}: " . $exception->getMessage());
    }
}
