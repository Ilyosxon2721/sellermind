<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesPassService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Wildberries warehouse passes
 *
 * WB Marketplace API:
 * - GET /api/v3/passes/offices - Get list of offices requiring passes
 * - GET /api/v3/passes - Get list of passes
 * - POST /api/v3/passes - Create pass
 * - PATCH /api/v3/passes/{passId} - Update pass
 * - DELETE /api/v3/passes/{passId} - Delete pass
 */
class WildberriesPassService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get list of WB offices that require passes
     */
    public function getOfficesRequiringPasses(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('marketplace', '/api/v3/passes/offices');

            Log::info('WB offices requiring passes fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB offices requiring passes', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get list of passes
     */
    public function getPasses(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('marketplace', '/api/v3/passes');

            Log::info('WB passes fetched', [
                'account_id' => $account->id,
                'count' => count($response['passes'] ?? []),
            ]);

            return $response['passes'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB passes', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new pass
     *
     * @param  array  $passData  Pass data
     *                           Required fields:
     *                           - firstName: string
     *                           - lastName: string
     *                           - carModel: string (optional)
     *                           - carNumber: string (optional)
     *                           - officeId: string (warehouse ID)
     *                           - dateFrom: string (YYYY-MM-DD)
     *                           - dateTo: string (YYYY-MM-DD)
     * @return array Created pass data with ID
     */
    public function createPass(MarketplaceAccount $account, array $passData): array
    {
        $this->validatePassData($passData);

        try {
            $response = $this->httpClient->post('marketplace', '/api/v3/passes', $passData);

            Log::info('WB pass created', [
                'account_id' => $account->id,
                'pass_id' => $response['id'] ?? null,
                'office_id' => $passData['officeId'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create WB pass', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'data' => $passData,
            ]);

            throw $e;
        }
    }

    /**
     * Update existing pass
     *
     * @param  string  $passId  Pass ID
     * @param  array  $passData  Updated pass data
     * @return array Updated pass data
     */
    public function updatePass(MarketplaceAccount $account, string $passId, array $passData): array
    {
        try {
            $response = $this->httpClient->patch('marketplace', "/api/v3/passes/{$passId}", $passData);

            Log::info('WB pass updated', [
                'account_id' => $account->id,
                'pass_id' => $passId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to update WB pass', [
                'account_id' => $account->id,
                'pass_id' => $passId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete pass
     *
     * @param  string  $passId  Pass ID
     */
    public function deletePass(MarketplaceAccount $account, string $passId): bool
    {
        try {
            $this->httpClient->delete('marketplace', "/api/v3/passes/{$passId}");

            Log::info('WB pass deleted', [
                'account_id' => $account->id,
                'pass_id' => $passId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete WB pass', [
                'account_id' => $account->id,
                'pass_id' => $passId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get passes expiring soon
     *
     * @param  int  $daysAhead  Check passes expiring within N days
     */
    public function getExpiringSoon(MarketplaceAccount $account, int $daysAhead = 7): array
    {
        $passes = $this->getPasses($account);
        $cutoffDate = now()->addDays($daysAhead);
        $expiring = [];

        foreach ($passes as $pass) {
            $dateTo = $pass['dateTo'] ?? null;
            if (! $dateTo) {
                continue;
            }

            try {
                $expiryDate = \Carbon\Carbon::parse($dateTo);
                if ($expiryDate->lessThanOrEqualTo($cutoffDate) && $expiryDate->greaterThanOrEqualTo(now())) {
                    $expiring[] = $pass;
                }
            } catch (\Exception $e) {
                Log::warning('Invalid date in pass', [
                    'pass_id' => $pass['id'] ?? 'unknown',
                    'date_to' => $dateTo,
                ]);
            }
        }

        return $expiring;
    }

    /**
     * Get expired passes
     */
    public function getExpired(MarketplaceAccount $account): array
    {
        $passes = $this->getPasses($account);
        $expired = [];

        foreach ($passes as $pass) {
            $dateTo = $pass['dateTo'] ?? null;
            if (! $dateTo) {
                continue;
            }

            try {
                $expiryDate = \Carbon\Carbon::parse($dateTo);
                if ($expiryDate->lessThan(now())) {
                    $expired[] = $pass;
                }
            } catch (\Exception $e) {
                Log::warning('Invalid date in pass', [
                    'pass_id' => $pass['id'] ?? 'unknown',
                    'date_to' => $dateTo,
                ]);
            }
        }

        return $expired;
    }

    /**
     * Cleanup expired passes (auto-delete)
     *
     * @return int Number of deleted passes
     */
    public function cleanupExpiredPasses(MarketplaceAccount $account): int
    {
        $expired = $this->getExpired($account);
        $deleted = 0;

        foreach ($expired as $pass) {
            $passId = $pass['id'] ?? null;
            if (! $passId) {
                continue;
            }

            try {
                $this->deletePass($account, $passId);
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Failed to delete expired pass during cleanup', [
                    'account_id' => $account->id,
                    'pass_id' => $passId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WB expired passes cleanup completed', [
            'account_id' => $account->id,
            'deleted' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Validate pass data
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePassData(array $passData): void
    {
        $required = ['firstName', 'lastName', 'officeId', 'dateFrom', 'dateTo'];

        foreach ($required as $field) {
            if (empty($passData[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required for pass creation");
            }
        }

        // Validate dates
        try {
            $dateFrom = \Carbon\Carbon::parse($passData['dateFrom']);
            $dateTo = \Carbon\Carbon::parse($passData['dateTo']);

            if ($dateTo->lessThan($dateFrom)) {
                throw new \InvalidArgumentException('dateTo must be greater than or equal to dateFrom');
            }

            if ($dateFrom->lessThan(now()->startOfDay())) {
                throw new \InvalidArgumentException('dateFrom cannot be in the past');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
        }
    }
}
