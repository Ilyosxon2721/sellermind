<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PromotionExpiringNotification;
use App\Services\PromotionService;
use Illuminate\Console\Command;

class ProcessPromotions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'promotions:process
                            {--create-auto : Create automatic promotions for slow-moving products}
                            {--notify-expiring : Send notifications for expiring promotions}
                            {--company= : Process specific company ID}';

    /**
     * The console command description.
     */
    protected $description = 'Process promotions: create automatic ones and notify about expiring';

    /**
     * Execute the console command.
     */
    public function handle(PromotionService $promotionService): int
    {
        $createAuto = $this->option('create-auto');
        $notifyExpiring = $this->option('notify-expiring');
        $companyId = $this->option('company');

        // If no options specified, do both
        if (!$createAuto && !$notifyExpiring) {
            $createAuto = true;
            $notifyExpiring = true;
        }

        if ($createAuto) {
            $this->info('Creating automatic promotions for slow-moving products...');
            $this->createAutomaticPromotions($promotionService, $companyId);
        }

        if ($notifyExpiring) {
            $this->info('Checking for expiring promotions...');
            $this->notifyExpiringPromotions($companyId);
        }

        $this->info('Done!');

        return Command::SUCCESS;
    }

    /**
     * Create automatic promotions for slow-moving products.
     */
    protected function createAutomaticPromotions(PromotionService $promotionService, ?int $companyId): void
    {
        $companies = $companyId
            ? Company::where('id', $companyId)->get()
            : Company::all();

        $totalCreated = 0;

        foreach ($companies as $company) {
            $this->line("Processing company: {$company->name} (ID: {$company->id})");

            // Check if company already has an active automatic promotion
            $hasActiveAuto = Promotion::where('company_id', $company->id)
                ->automatic()
                ->active()
                ->exists();

            if ($hasActiveAuto) {
                $this->line("  ⏭️  Skipping - already has active automatic promotion");
                continue;
            }

            // Detect slow-moving products
            $slowMoving = $promotionService->detectSlowMovingProducts($company->id, [
                'min_days_no_sale' => 30,
                'min_stock' => 5,
                'min_price' => 100,
            ]);

            if ($slowMoving->isEmpty()) {
                $this->line("  ✅ No slow-moving products found");
                continue;
            }

            try {
                // Create automatic promotion
                $promotion = $promotionService->createAutomaticPromotion(
                    $company,
                    $slowMoving->toArray(),
                    [
                        'duration_days' => 30,
                        'max_discount' => 50,
                    ]
                );

                // Apply promotion immediately
                $count = $promotionService->applyPromotion($promotion);

                $this->line("  ✅ Created promotion with {$slowMoving->count()} products, applied to {$count} variants");
                $totalCreated++;

                // Notify company owner
                $owner = $company->users()->wherePivot('role', 'owner')->first();
                if ($owner) {
                    $this->line("  📧 Notifying owner: {$owner->name}");
                    // You can send a notification here if needed
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to create promotion: {$e->getMessage()}");
            }
        }

        $this->info("Created {$totalCreated} automatic promotions");
    }

    /**
     * Notify about expiring promotions.
     */
    protected function notifyExpiringPromotions(?int $companyId): void
    {
        $query = Promotion::expiringSoon(3)
            ->with(['company']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $expiringPromotions = $query->get();

        $this->line("Found {$expiringPromotions->count()} expiring promotions");

        foreach ($expiringPromotions as $promotion) {
            $daysLeft = $promotion->getDaysUntilExpiration();

            $this->line("Processing: {$promotion->name} (Company: {$promotion->company->name}, {$daysLeft} days left)");

            // Get company owner
            $owner = $promotion->company->users()
                ->wherePivot('role', 'owner')
                ->first();

            if (!$owner) {
                $this->warn("  ⚠️  No owner found for company {$promotion->company->name}");
                continue;
            }

            try {
                // Send notification
                $owner->notify(new PromotionExpiringNotification($promotion, $daysLeft));

                // Mark as notified
                $promotion->update(['expiry_notification_sent_at' => now()]);

                $this->line("  ✅ Notification sent to {$owner->name}");
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to send notification: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$expiringPromotions->count()} expiring promotions");
    }
}
