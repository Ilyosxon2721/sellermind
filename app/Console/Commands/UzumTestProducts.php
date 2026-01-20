<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class UzumTestProducts extends Command
{
    protected $signature = 'uzum:test-products {--account= : Account ID}';
    protected $description = 'Test Uzum products API endpoint directly';

    public function handle(): int
    {
        $accountId = $this->option('account');

        $query = MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true);
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $account = $query->first();

        if (!$account) {
            $this->error('No active Uzum account found');
            return self::FAILURE;
        }

        $this->info("Testing account #{$account->id}: {$account->name}");

        // 1. Check raw DB value
        $rawApiKey = $account->getAttributes()['uzum_api_key'] ?? null;
        $this->line("\n1. Raw DB uzum_api_key: " . ($rawApiKey ? substr($rawApiKey, 0, 30) . '...' : 'NULL'));
        $this->line("   Looks encrypted: " . ($rawApiKey && str_starts_with($rawApiKey, 'eyJ') ? 'YES' : 'NO'));

        // 2. Try to decrypt
        $decryptedToken = null;
        if ($rawApiKey) {
            try {
                $decryptedToken = Crypt::decryptString($rawApiKey);
                $this->info("2. Decryption SUCCESS: " . substr($decryptedToken, 0, 20) . '...');
            } catch (\Exception $e) {
                $this->warn("2. Decryption FAILED: " . $e->getMessage());
                $decryptedToken = $rawApiKey; // Use as-is
                $this->line("   Using raw value as token");
            }
        }

        // 3. Get via accessor
        $accessorToken = $account->uzum_api_key;
        $this->line("\n3. Token via accessor: " . ($accessorToken ? substr($accessorToken, 0, 30) . '...' : 'NULL'));
        $this->line("   Accessor token looks encrypted: " . ($accessorToken && str_starts_with($accessorToken, 'eyJ') ? 'YES (BAD!)' : 'NO'));

        // 4. Get shop_id
        $shopId = $account->shop_id;
        $credentialsJson = $account->credentials_json ?? [];
        $shopIds = $credentialsJson['shop_ids'] ?? [];

        $this->line("\n4. Shop configuration:");
        $this->line("   shop_id field: " . ($shopId ?: 'NULL'));
        $this->line("   credentials_json shop_ids: " . json_encode($shopIds));

        $testShopId = $shopIds[0] ?? $shopId ?? null;
        if (!$testShopId) {
            $this->error("No shop_id configured!");
            return self::FAILURE;
        }

        // Parse if comma-separated
        if (is_string($testShopId) && str_contains($testShopId, ',')) {
            $testShopId = explode(',', $testShopId)[0];
        }

        $this->line("   Using shop_id for test: {$testShopId}");

        // 5. Test orders endpoint (should work)
        $this->line("\n5. Testing ORDERS endpoint (/v2/fbs/orders)...");
        $this->testEndpoint($accessorToken, "/v2/fbs/orders?shopIds={$testShopId}&page=0&size=1&status=CREATED");

        // 6. Test products endpoint (fails?)
        $this->line("\n6. Testing PRODUCTS endpoint (/v1/product/shop/{$testShopId})...");
        $this->testEndpoint($accessorToken, "/v1/product/shop/{$testShopId}?page=0&size=1");

        // 7. Test shops endpoint
        $this->line("\n7. Testing SHOPS endpoint (/v1/shops)...");
        $this->testEndpoint($accessorToken, "/v1/shops");

        return self::SUCCESS;
    }

    protected function testEndpoint(string $token, string $path): void
    {
        $baseUrl = 'https://api-seller.uzum.uz/api/seller-openapi';
        $url = $baseUrl . $path;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $status = $response->status();
            $this->line("   Status: {$status}");

            if ($response->successful()) {
                $this->info("   SUCCESS!");
                $body = $response->json();
                $this->line("   Response keys: " . implode(', ', array_keys($body)));
            } else {
                $this->error("   FAILED!");
                $this->line("   Response: " . substr($response->body(), 0, 300));
            }
        } catch (\Exception $e) {
            $this->error("   EXCEPTION: " . $e->getMessage());
        }
    }
}
