<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class DebugUzumToken extends Command
{
    protected $signature = 'uzum:debug-token {--account= : Account ID}';

    protected $description = 'Debug Uzum API token decryption and test API connectivity';

    public function handle(): int
    {
        $accountId = $this->option('account');

        $query = MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true);
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->error('No active Uzum accounts found');

            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $this->info("\n=== Account #{$account->id}: {$account->name} ===");

            // Get raw value from DB
            $rawApiKey = $account->getAttributes()['uzum_api_key'] ?? null;
            $rawAccessToken = $account->getAttributes()['uzum_access_token'] ?? null;
            $rawGeneralApiKey = $account->getAttributes()['api_key'] ?? null;

            $this->line("\nRaw DB values:");
            $this->line('  uzum_api_key: '.($rawApiKey ? substr($rawApiKey, 0, 50).'...' : 'NULL'));
            $this->line('  uzum_access_token: '.($rawAccessToken ? substr($rawAccessToken, 0, 50).'...' : 'NULL'));
            $this->line('  api_key: '.($rawGeneralApiKey ? substr($rawGeneralApiKey, 0, 50).'...' : 'NULL'));

            // Check if values look encrypted (Laravel encrypted strings start with eyJ)
            $this->line("\nEncryption check:");
            $this->line('  uzum_api_key looks encrypted: '.($rawApiKey && str_starts_with($rawApiKey, 'eyJ') ? 'YES' : 'NO'));
            $this->line('  uzum_access_token looks encrypted: '.($rawAccessToken && str_starts_with($rawAccessToken, 'eyJ') ? 'YES' : 'NO'));
            $this->line('  api_key looks encrypted: '.($rawGeneralApiKey && str_starts_with($rawGeneralApiKey, 'eyJ') ? 'YES' : 'NO'));

            // Try decryption
            $this->line("\nDecryption attempts:");

            foreach (['uzum_api_key' => $rawApiKey, 'uzum_access_token' => $rawAccessToken, 'api_key' => $rawGeneralApiKey] as $field => $raw) {
                if (! $raw) {
                    continue;
                }

                try {
                    $decrypted = Crypt::decryptString($raw);
                    $this->info("  {$field}: Decryption SUCCESS");
                    $this->line('    Decrypted value starts with: '.substr($decrypted, 0, 20).'...');
                    $this->line('    Decrypted length: '.strlen($decrypted));
                } catch (\Exception $e) {
                    $this->warn("  {$field}: Decryption FAILED - ".$e->getMessage());
                }
            }

            // Get token via accessor
            $this->line("\nAccessor values:");
            $accessorToken = $account->uzum_api_key ?? $account->uzum_access_token ?? $account->api_key;
            $this->line('  Final token via accessor: '.($accessorToken ? substr($accessorToken, 0, 30).'...' : 'NULL'));
            $this->line('  Token length: '.($accessorToken ? strlen($accessorToken) : 0));
            $this->line('  Token looks encrypted: '.($accessorToken && str_starts_with($accessorToken, 'eyJ') ? 'YES (BAD!)' : 'NO (GOOD)'));

            // Get auth headers
            $headers = $account->getUzumAuthHeaders();
            $authHeader = $headers['Authorization'] ?? null;
            $this->line("\nAuth header:");
            $this->line('  Authorization: '.($authHeader ? substr($authHeader, 0, 30).'...' : 'NULL'));

            // Test API call
            $this->line("\nTesting API call to /v1/shops...");

            if ($authHeader) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => $authHeader,
                            'Accept' => 'application/json',
                        ])
                        ->get('https://api-seller.uzum.uz/api/seller-openapi/v1/shops');

                    $this->line('  HTTP Status: '.$response->status());

                    if ($response->successful()) {
                        $this->info('  API call SUCCESS!');
                        $body = $response->json();
                        $shops = $body['payload'] ?? $body ?? [];
                        $this->line('  Shops found: '.count($shops));
                    } else {
                        $this->error('  API call FAILED!');
                        $this->line('  Response: '.substr($response->body(), 0, 200));
                    }
                } catch (\Exception $e) {
                    $this->error('  API call EXCEPTION: '.$e->getMessage());
                }
            } else {
                $this->warn('  Skipping API test - no auth header');
            }

            // Suggest fix
            $this->line("\n--- Diagnosis ---");
            if ($accessorToken && str_starts_with($accessorToken, 'eyJ')) {
                $this->error('PROBLEM: Token is still encrypted after accessor!');
                $this->warn('This means decryption failed and accessor returned the encrypted value.');
                $this->warn('Possible causes:');
                $this->warn('  1. APP_KEY mismatch between when token was saved and now');
                $this->warn('  2. Token was double-encrypted');
                $this->line("\nFIX: Update token directly in DB without encryption:");
                $this->line("UPDATE marketplace_accounts SET uzum_api_key = 'YOUR_PLAIN_TOKEN' WHERE id = {$account->id};");
            } elseif (! $accessorToken) {
                $this->error('PROBLEM: No token found!');
            } else {
                $this->info('Token looks correct (not encrypted).');
            }
        }

        return self::SUCCESS;
    }
}
