<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiringNotification;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:check-expiring
                          {--notify-days=7,3,1 : Days before expiration to send notifications}
                          {--mark-expired : Mark expired subscriptions}';

    /**
     * The console command description.
     */
    protected $description = 'Check for expiring subscriptions and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expiring subscriptions...');

        $notifyDays = array_map('intval', explode(',', $this->option('notify-days')));
        $markExpired = $this->option('mark-expired');

        $totalNotifications = 0;
        $totalExpired = 0;

        // Check for expiring subscriptions
        foreach ($notifyDays as $days) {
            $count = $this->notifyExpiringSubscriptions($days);
            $totalNotifications += $count;

            if ($count > 0) {
                $this->info("Sent {$count} notifications for subscriptions expiring in {$days} days");
            }
        }

        // Mark expired subscriptions
        if ($markExpired) {
            $totalExpired = $this->markExpiredSubscriptions();

            if ($totalExpired > 0) {
                $this->info("Marked {$totalExpired} subscriptions as expired");
            }
        }

        if ($totalNotifications === 0 && $totalExpired === 0) {
            $this->info('No subscriptions to notify or mark as expired');
        } else {
            $this->info("Total notifications sent: {$totalNotifications}");
            if ($markExpired) {
                $this->info("Total subscriptions expired: {$totalExpired}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Notify users about expiring subscriptions
     */
    protected function notifyExpiringSubscriptions(int $days): int
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = $targetDate->copy()->endOfDay();

        // Get subscriptions expiring on target date
        $subscriptions = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$targetDate, $endDate])
            ->with(['company.users', 'plan'])
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $company = $subscription->company;

            if (!$company) {
                continue;
            }

            // Notify company owner
            $owner = $company->users()
                ->wherePivot('role', 'owner')
                ->first();

            if ($owner) {
                try {
                    $owner->notify(new SubscriptionExpiringNotification($subscription, $days));
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to notify user {$owner->id}: {$e->getMessage()}");
                }
            }

            // Also notify all managers
            $managers = $company->users()
                ->wherePivot('role', 'manager')
                ->get();

            foreach ($managers as $manager) {
                try {
                    $manager->notify(new SubscriptionExpiringNotification($subscription, $days));
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to notify manager {$manager->id}: {$e->getMessage()}");
                }
            }
        }

        return $count;
    }

    /**
     * Mark expired subscriptions and notify users
     */
    protected function markExpiredSubscriptions(): int
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->with(['company.users', 'plan'])
            ->get();

        $count = 0;

        foreach ($expiredSubscriptions as $subscription) {
            DB::transaction(function () use ($subscription, &$count) {
                // Mark as expired
                $subscription->update([
                    'status' => 'expired',
                ]);

                $company = $subscription->company;

                if (!$company) {
                    return;
                }

                // Notify company owner
                $owner = $company->users()
                    ->wherePivot('role', 'owner')
                    ->first();

                if ($owner) {
                    try {
                        $owner->notify(new SubscriptionExpiredNotification($subscription));
                    } catch (\Exception $e) {
                        $this->error("Failed to notify owner {$owner->id}: {$e->getMessage()}");
                    }
                }

                // Also notify all managers
                $managers = $company->users()
                    ->wherePivot('role', 'manager')
                    ->get();

                foreach ($managers as $manager) {
                    try {
                        $manager->notify(new SubscriptionExpiredNotification($subscription));
                    } catch (\Exception $e) {
                        $this->error("Failed to notify manager {$manager->id}: {$e->getMessage()}");
                    }
                }

                $count++;
            });
        }

        return $count;
    }
}
