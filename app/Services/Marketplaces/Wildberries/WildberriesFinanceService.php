<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesFinanceService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for Wildberries financial reports and balance
 *
 * WB Statistics API:
 * - GET /api/v1/account/balance - Get seller balance
 * - GET /api/v5/supplier/reportDetailByPeriod - Detailed sales report
 *
 * WB Documents API:
 * - GET /api/v1/documents/categories - Get document categories
 * - GET /api/v1/documents/list - Get documents list
 * - GET /api/v1/documents/download - Download document
 * - GET /api/v1/documents/download/all - Download all documents
 */
class WildberriesFinanceService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get seller account balance
     *
     * @return array Balance information
     */
    public function getBalance(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('statistics', '/api/v1/account/balance');

            Log::info('WB balance fetched', [
                'account_id' => $account->id,
                'balance' => $response['balance'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB balance', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get detailed sales report by period
     *
     * @param  Carbon  $dateFrom  Start date
     * @param  Carbon|null  $dateTo  End date (default: now)
     * @param  int  $limit  Records per page (max 100000, default 1000)
     * @param  int  $rrdid  Last record ID for pagination
     * @return array Report data
     */
    public function getDetailedReport(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        ?Carbon $dateTo = null,
        int $limit = 1000,
        int $rrdid = 0
    ): array {
        $dateTo = $dateTo ?? now();

        if ($limit > 100000) {
            $limit = 100000;
        }

        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
                'limit' => $limit,
                'rrdid' => $rrdid,
            ];

            $response = $this->httpClient->get('statistics', '/api/v5/supplier/reportDetailByPeriod', $params);

            Log::info('WB detailed report fetched', [
                'account_id' => $account->id,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'records' => is_array($response) ? count($response) : 0,
            ]);

            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB detailed report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get full detailed report with pagination
     *
     * @return array All report records
     */
    public function getFullDetailedReport(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        ?Carbon $dateTo = null
    ): array {
        $allRecords = [];
        $rrdid = 0;
        $limit = 100000; // Max per request

        do {
            $batch = $this->getDetailedReport($account, $dateFrom, $dateTo, $limit, $rrdid);

            if (empty($batch)) {
                break;
            }

            $allRecords = array_merge($allRecords, $batch);

            // Get last record ID for next page
            $lastRecord = end($batch);
            $rrdid = $lastRecord['rrd_id'] ?? 0;

            // If we got less than limit, we're done
            if (count($batch) < $limit) {
                break;
            }
        } while (true);

        Log::info('WB full detailed report fetched', [
            'account_id' => $account->id,
            'total_records' => count($allRecords),
        ]);

        return $allRecords;
    }

    /**
     * Get document categories
     *
     * @return array Categories list
     */
    public function getDocumentCategories(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('statistics', '/api/v1/documents/categories');

            Log::info('WB document categories fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB document categories', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get documents list
     *
     * @param  string|null  $category  Document category
     * @param  Carbon|null  $dateFrom  Start date
     * @param  Carbon|null  $dateTo  End date
     * @return array Documents list
     */
    public function getDocuments(
        MarketplaceAccount $account,
        ?string $category = null,
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null
    ): array {
        try {
            $params = [];

            if ($category) {
                $params['category'] = $category;
            }

            if ($dateFrom) {
                $params['dateFrom'] = $dateFrom->format('Y-m-d');
            }

            if ($dateTo) {
                $params['dateTo'] = $dateTo->format('Y-m-d');
            }

            $response = $this->httpClient->get('statistics', '/api/v1/documents/list', $params);

            Log::info('WB documents list fetched', [
                'account_id' => $account->id,
                'category' => $category,
                'count' => count($response['documents'] ?? []),
            ]);

            return $response['documents'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB documents list', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Download document
     *
     * @param  string  $documentId  Document ID
     * @param  bool  $save  Save to storage
     * @return array ['content' => string, 'file_path' => string|null, 'filename' => string]
     */
    public function downloadDocument(
        MarketplaceAccount $account,
        string $documentId,
        bool $save = true
    ): array {
        try {
            $params = ['documentId' => $documentId];

            // API returns binary data (usually PDF or Excel)
            $response = $this->httpClient->get('statistics', '/api/v1/documents/download', $params, [
                'raw_response' => true,
            ]);

            $filename = "wb-document-{$documentId}.pdf";
            $filePath = null;

            if ($save) {
                $filePath = $this->saveDocument($account, $documentId, $response, $filename);
            }

            Log::info('WB document downloaded', [
                'account_id' => $account->id,
                'document_id' => $documentId,
                'saved' => $save,
            ]);

            return [
                'content' => $response,
                'file_path' => $filePath,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to download WB document', [
                'account_id' => $account->id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Download all documents for period
     *
     * @param  bool  $save  Save to storage
     * @return array List of downloaded documents
     */
    public function downloadAllDocuments(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo,
        bool $save = true
    ): array {
        $documents = $this->getDocuments($account, null, $dateFrom, $dateTo);
        $downloaded = [];

        foreach ($documents as $doc) {
            $documentId = $doc['documentId'] ?? $doc['id'] ?? null;

            if (! $documentId) {
                continue;
            }

            try {
                $result = $this->downloadDocument($account, $documentId, $save);
                $downloaded[] = array_merge($doc, $result);
            } catch (\Exception $e) {
                Log::error('Failed to download document in batch', [
                    'account_id' => $account->id,
                    'document_id' => $documentId,
                    'error' => $e->getMessage(),
                ]);

                $downloaded[] = array_merge($doc, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WB bulk documents download completed', [
            'account_id' => $account->id,
            'total' => count($documents),
            'downloaded' => count(array_filter($downloaded, fn ($d) => ! isset($d['error']))),
        ]);

        return $downloaded;
    }

    /**
     * Calculate financial summary from detailed report
     *
     * IMPORTANT: WB API returns amounts in the seller's account currency (currency_name field).
     * For Uzbekistan sellers, this is UZS, NOT RUB despite field names like "delivery_rub".
     * The currency is determined from the first record's currency_name field.
     *
     * @param  array  $reportData  Detailed report data
     * @return array Summary statistics with currency info
     */
    public function calculateSummary(array $reportData): array
    {
        $summary = [
            'total_sales' => 0,
            'total_returns' => 0,
            'total_commission' => 0,
            'total_logistics' => 0,
            'total_penalty' => 0,
            'total_storage' => 0,
            'net_profit' => 0,
            'orders_count' => 0,
            'returns_count' => 0,
            'currency' => 'RUB', // Default, will be detected from data
        ];

        // Detect currency from first record (WB returns currency_name in each record)
        if (! empty($reportData)) {
            $firstRecord = $reportData[0] ?? null;
            if ($firstRecord && isset($firstRecord['currency_name'])) {
                $summary['currency'] = $firstRecord['currency_name'];
            }
        }

        foreach ($reportData as $record) {
            $operationType = $record['supplier_oper_name'] ?? '';

            // Sale - use supplier_oper_name, not sa_name (which is article code)
            if ($operationType === 'Продажа') {
                $summary['total_sales'] += $record['retail_amount'] ?? 0;
                // Commission is in ppvz_sales_commission field
                $summary['total_commission'] += abs($record['ppvz_sales_commission'] ?? 0);
                $summary['orders_count']++;
            }

            // Return
            if ($operationType === 'Возврат') {
                $summary['total_returns'] += abs($record['retail_amount'] ?? 0);
                // Returns also have negative commission (refunded)
                $summary['total_commission'] -= abs($record['ppvz_sales_commission'] ?? 0);
                $summary['returns_count']++;
            }

            // Logistics - separate operation type
            // Note: field is called "delivery_rub" but contains amount in account currency
            if ($operationType === 'Логистика') {
                $summary['total_logistics'] += abs($record['delivery_rub'] ?? 0);
            }

            // Storage fee from report (in addition to paid_storage API)
            if ($operationType === 'Хранение') {
                $summary['total_storage'] += abs($record['storage_fee'] ?? 0);
            }

            // Penalty - can be in any record type
            if (isset($record['penalty']) && $record['penalty'] != 0) {
                $summary['total_penalty'] += abs($record['penalty']);
            }
        }

        $summary['net_profit'] = $summary['total_sales']
            - $summary['total_returns']
            - $summary['total_commission']
            - $summary['total_logistics']
            - $summary['total_storage']
            - $summary['total_penalty'];

        return $summary;
    }

    /**
     * Get paid storage fees for period
     * GET /api/v1/paid_storage
     *
     * @return float Total storage fees in RUB
     */
    public function getPaidStorageFees(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): float {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('statistics', '/api/v1/paid_storage', $params);

            // Response is array of storage records with warehousePrice field
            $totalStorage = 0;
            if (is_array($response)) {
                foreach ($response as $record) {
                    $totalStorage += abs((float) ($record['warehousePrice'] ?? 0));
                }
            }

            Log::info('WB paid storage fees fetched', [
                'account_id' => $account->id,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'total' => $totalStorage,
            ]);

            return $totalStorage;
        } catch (\Exception $e) {
            Log::warning('Failed to get WB paid storage fees', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            // Return 0 if API not available or error
            return 0;
        }
    }

    /**
     * Get full expense summary including storage
     *
     * IMPORTANT: WB API returns amounts in the seller's account currency.
     * For Uzbekistan sellers, amounts are in UZS, NOT RUB.
     * The currency is detected from the API response (currency_name field).
     *
     * @return array Full expense summary with detected currency
     */
    public function getExpensesSummary(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        // Get base summary from detailed report
        $reportData = $this->getFullDetailedReport($account, $dateFrom, $dateTo);
        $summary = $this->calculateSummary($reportData);

        // Detect currency from report data (UZS for Uzbekistan sellers)
        $currency = $summary['currency'] ?? 'RUB';

        // Storage from report + paid_storage API (they may overlap, take max)
        $paidStorageFees = $this->getPaidStorageFees($account, $dateFrom, $dateTo);
        $reportStorageFees = $summary['total_storage'] ?? 0;
        $totalStorage = max($paidStorageFees, $reportStorageFees);

        $commission = $summary['total_commission'];
        $logistics = $summary['total_logistics'];
        $penalties = $summary['total_penalty'];

        Log::info('WB getExpensesSummary calculated', [
            'account_id' => $account->id,
            'currency' => $currency,
            'commission' => $commission,
            'logistics' => $logistics,
            'storage' => $totalStorage,
            'penalties' => $penalties,
        ]);

        return [
            'commission' => $commission,
            'logistics' => $logistics,
            'storage' => $totalStorage,
            'advertising' => 0, // TODO: Add advertising API if needed
            'penalties' => $penalties,
            'returns' => $summary['total_returns'],
            'other' => 0,
            'total' => $commission + $logistics + $totalStorage + $penalties,
            'orders_count' => $summary['orders_count'],
            'returns_count' => $summary['returns_count'],
            'gross_revenue' => $summary['total_sales'],
            'currency' => $currency, // Actual currency from API (UZS for Uzbekistan)
        ];
    }

    /**
     * Save document to storage
     *
     * @return string File path
     */
    protected function saveDocument(
        MarketplaceAccount $account,
        string $documentId,
        string $content,
        string $filename
    ): string {
        $timestamp = now()->format('Y-m-d_His');
        $path = "marketplace/documents/account-{$account->id}/{$timestamp}-{$filename}";

        Storage::disk('local')->put($path, $content);

        Log::info('WB document saved to storage', [
            'account_id' => $account->id,
            'path' => $path,
            'size' => strlen($content),
        ]);

        return $path;
    }
}
