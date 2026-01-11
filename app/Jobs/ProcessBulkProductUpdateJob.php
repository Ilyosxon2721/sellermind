<?php

namespace App\Jobs;

use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\BulkOperationCompletedNotification;
use App\Notifications\CriticalErrorNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBulkProductUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $companyId,
        protected int $userId,
        protected string $filePath
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting bulk product update', [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'file' => $this->filePath,
        ]);

        $csvData = array_map(function($row) {
            return str_getcsv($row, ';');
        }, file($this->filePath));

        // Remove BOM if present
        if (isset($csvData[0][0])) {
            $csvData[0][0] = str_replace("\xEF\xBB\xBF", '', $csvData[0][0]);
        }

        // Remove header
        array_shift($csvData);

        $updated = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            foreach ($csvData as $row) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Parse row
                $variantId = $row[4] ?? null;
                $newPurchasePrice = isset($row[7]) && $row[7] !== '' ? (float) $row[7] : null;
                $newRetailPrice = isset($row[8]) && $row[8] !== '' ? (float) $row[8] : null;
                $newOldPrice = isset($row[9]) && $row[9] !== '' ? (float) $row[9] : null;
                $newStock = isset($row[10]) && $row[10] !== '' ? (int) $row[10] : null;
                $isActive = isset($row[11]) ? strtolower(trim($row[11])) === 'yes' : null;

                // Find variant
                $variant = ProductVariant::with('product')
                    ->where('id', $variantId)
                    ->whereHas('product', function($q) {
                        $q->where('company_id', $this->companyId);
                    })
                    ->first();

                if (!$variant) {
                    $errors[] = "Row {$rowNumber}: Variant not found (ID: {$variantId})";
                    continue;
                }

                // Prepare update data
                $updateData = [];

                if ($newPurchasePrice !== null) {
                    $updateData['purchase_price'] = $newPurchasePrice;
                }
                if ($newRetailPrice !== null) {
                    $updateData['retail_price'] = $newRetailPrice;
                }
                if ($newOldPrice !== null) {
                    $updateData['old_price'] = $newOldPrice;
                }
                if ($newStock !== null) {
                    $updateData['stock_default'] = $newStock;
                }
                if ($isActive !== null) {
                    $updateData['is_active'] = $isActive;
                }

                if (!empty($updateData)) {
                    $variant->update($updateData);
                    $updated++;
                }
            }

            DB::commit();

            Log::info('Bulk product update completed', [
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'updated' => $updated,
                'errors' => count($errors),
            ]);

            // Send notification to user
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new BulkOperationCompletedNotification($updated, $errors));
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk product update failed', [
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // TODO: Send error notification to user
            throw $e;
        } finally {
            // Clean up temp file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk product update job failed', [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'exception' => $exception->getMessage(),
        ]);

        // Clean up temp file
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        // Send failure notification to user
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new CriticalErrorNotification(
                'Ошибка массового обновления товаров',
                'Не удалось завершить импорт товаров из-за ошибки.',
                $exception->getMessage()
            ));
        }
    }
}
