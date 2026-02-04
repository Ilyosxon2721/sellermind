<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PromotionExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPromotionExpiringNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $companyId;

    public int $daysThreshold;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId, int $daysThreshold = 3)
    {
        $this->companyId = $companyId;
        $this->daysThreshold = $daysThreshold;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Checking expiring promotions for company {$this->companyId}");

            $company = Company::find($this->companyId);

            if (! $company || ! $company->is_active) {
                Log::warning("Company {$this->companyId} not found or inactive");

                return;
            }

            // Find promotions expiring soon
            $expiringPromotions = Promotion::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->where('end_date', '>', now())
                ->where('end_date', '<=', now()->addDays($this->daysThreshold))
                ->whereDoesntHave('notifications', function ($query) {
                    $query->where('type', PromotionExpiringNotification::class)
                        ->where('created_at', '>=', now()->subDay());
                })
                ->with('products')
                ->get();

            if ($expiringPromotions->isEmpty()) {
                Log::info("No expiring promotions found for company {$this->companyId}");

                return;
            }

            Log::info("Found {$expiringPromotions->count()} expiring promotions for company {$this->companyId}");

            // Get company users to notify
            $users = User::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                Log::warning("No active users found for company {$this->companyId}");

                return;
            }

            // Send notifications
            $notifiedCount = 0;
            foreach ($expiringPromotions as $promotion) {
                try {
                    foreach ($users as $user) {
                        $user->notify(new PromotionExpiringNotification($promotion));
                    }

                    $notifiedCount++;
                    Log::info("Sent expiring notification for promotion {$promotion->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send notification for promotion {$promotion->id}: ".$e->getMessage());
                }
            }

            Log::info("Successfully sent {$notifiedCount} expiring promotion notifications for company {$this->companyId}");
        } catch (\Exception $e) {
            Log::error("Failed to send expiring notifications for company {$this->companyId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendPromotionExpiringNotificationsJob failed for company {$this->companyId}: ".$exception->getMessage());
    }
}
