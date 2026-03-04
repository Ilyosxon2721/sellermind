<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkProductUpdateJob;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller for bulk product operations
 * - Export products to Excel
 * - Import product updates from Excel (prices, status, etc.)
 * - Bulk actions (activate, deactivate, update category, etc.)
 */
class ProductBulkController extends Controller
{
    /**
     * Export products with variants to Excel
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'channel_id' => ['nullable', 'integer', 'exists:channels,id'],
            'include_archived' => ['nullable', 'boolean'],
        ]);

        $companyId = $request->user()->company_id;

        $query = Product::query()
            ->forCompany($companyId)
            ->with(['variants', 'category']);

        // Filter by specific product IDs
        if ($request->filled('product_ids')) {
            $query->whereIn('id', $request->product_ids);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by marketplace channel
        if ($request->filled('channel_id')) {
            $query->whereHas('channelSettings', function ($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        // Include/exclude archived
        if (! $request->boolean('include_archived', false)) {
            $query->where('is_archived', false);
        }

        $products = $query->get();

        // Prepare data for CSV export
        $csvData = [];
        $csvData[] = [
            'Product ID',
            'Product Name',
            'Article',
            'Category',
            'Variant ID',
            'SKU',
            'Barcode',
            'Purchase Price',
            'Retail Price',
            'Old Price',
            'Stock',
            'Is Active',
            'Variant Options',
        ];

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $csvData[] = [
                    $product->id,
                    $product->name,
                    $product->article,
                    $product->category?->name ?? '',
                    $variant->id,
                    $variant->sku,
                    $variant->barcode ?? '',
                    $variant->purchase_price ?? 0,
                    $variant->retail_price ?? 0,
                    $variant->old_price ?? 0,
                    $variant->stock_default ?? 0,
                    $variant->is_active ? 'Yes' : 'No',
                    $this->formatVariantOptions($variant),
                ];
            }
        }

        // Create CSV file
        $filename = 'products_export_'.now()->format('Y-m-d_His').'.csv';
        $filePath = storage_path('app/temp/'.$filename);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $file = fopen($filePath, 'w');

        // Add BOM for UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($csvData as $row) {
            fputcsv($file, $row, ';'); // Use semicolon for Excel compatibility
        }

        fclose($file);

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    /**
     * Preview bulk import from Excel
     */
    public function previewImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $csvData = array_map(function ($row) {
            return str_getcsv($row, ';');
        }, file($file->getPathname()));

        // Remove BOM if present
        if (isset($csvData[0][0])) {
            $csvData[0][0] = str_replace("\xEF\xBB\xBF", '', $csvData[0][0]);
        }

        // Remove header row
        $header = array_shift($csvData);

        $companyId = $request->user()->company_id;
        $preview = [];
        $errors = [];
        $rowNumber = 1; // Start from 1 (after header)

        foreach ($csvData as $row) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Parse row
            $variantId = $row[4] ?? null;
            $sku = $row[5] ?? null;
            $newPurchasePrice = $row[7] ?? null;
            $newRetailPrice = $row[8] ?? null;
            $newOldPrice = $row[9] ?? null;
            $newStock = $row[10] ?? null;
            $isActive = isset($row[11]) ? strtolower(trim($row[11])) === 'yes' : null;

            // Validate variant
            $variant = null;
            if ($variantId) {
                $variant = ProductVariant::with('product')
                    ->where('id', $variantId)
                    ->whereHas('product', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->first();
            }

            if (! $variant) {
                $errors[] = "Row {$rowNumber}: Variant not found (ID: {$variantId})";

                continue;
            }

            // Prepare changes
            $changes = [];
            if ($newPurchasePrice !== null && $newPurchasePrice !== '' && $variant->purchase_price != $newPurchasePrice) {
                $changes['purchase_price'] = [
                    'old' => $variant->purchase_price,
                    'new' => (float) $newPurchasePrice,
                ];
            }
            if ($newRetailPrice !== null && $newRetailPrice !== '' && $variant->retail_price != $newRetailPrice) {
                $changes['retail_price'] = [
                    'old' => $variant->retail_price,
                    'new' => (float) $newRetailPrice,
                ];
            }
            if ($newOldPrice !== null && $newOldPrice !== '' && $variant->old_price != $newOldPrice) {
                $changes['old_price'] = [
                    'old' => $variant->old_price,
                    'new' => (float) $newOldPrice,
                ];
            }
            if ($newStock !== null && $newStock !== '' && $variant->stock_default != $newStock) {
                $changes['stock_default'] = [
                    'old' => $variant->stock_default,
                    'new' => (int) $newStock,
                ];
            }
            if ($isActive !== null && $variant->is_active != ($isActive === true)) {
                $changes['is_active'] = [
                    'old' => $variant->is_active,
                    'new' => $isActive,
                ];
            }

            if (! empty($changes)) {
                $preview[] = [
                    'row' => $rowNumber,
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'product_name' => $variant->product->name,
                    'changes' => $changes,
                ];
            }
        }

        return response()->json([
            'total_rows' => count($csvData),
            'changes_count' => count($preview),
            'errors_count' => count($errors),
            'preview' => array_slice($preview, 0, 100), // Show first 100 changes
            'errors' => $errors,
        ]);
    }

    /**
     * Apply bulk import from Excel
     */
    public function applyImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $csvData = array_map(function ($row) {
            return str_getcsv($row, ';');
        }, file($file->getPathname()));

        // Remove BOM if present
        if (isset($csvData[0][0])) {
            $csvData[0][0] = str_replace("\xEF\xBB\xBF", '', $csvData[0][0]);
        }

        // Remove header
        array_shift($csvData);

        // Store file temporarily and dispatch job
        $tempFilePath = storage_path('app/temp/import_'.uniqid().'.csv');
        if (! file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0755, true);
        }
        $file->move(dirname($tempFilePath), basename($tempFilePath));

        // Dispatch job for processing
        ProcessBulkProductUpdateJob::dispatch(
            $request->user()->company_id,
            $request->user()->id,
            $tempFilePath
        );

        return response()->json([
            'message' => 'Bulk update queued for processing. You will receive a notification when completed.',
        ], 202);
    }

    /**
     * Bulk update product variants (selected items)
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['integer', 'exists:product_variants,id'],
            'action' => ['required', 'string', Rule::in([
                'activate',
                'deactivate',
                'update_prices',
                'update_stock',
                'update_category',
            ])],
            'data' => ['required_if:action,update_prices,update_stock,update_category', 'array'],
        ]);

        $companyId = $request->user()->company_id;

        // Verify all variants belong to user's company
        $variantIds = $request->variant_ids;
        $validVariants = ProductVariant::whereIn('id', $variantIds)
            ->whereHas('product', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->pluck('id')
            ->toArray();

        if (count($validVariants) !== count($variantIds)) {
            return response()->json([
                'message' => 'Some variants do not belong to your company.',
            ], 403);
        }

        $updated = 0;

        DB::beginTransaction();
        try {
            switch ($request->action) {
                case 'activate':
                    $updated = ProductVariant::whereIn('id', $validVariants)
                        ->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $updated = ProductVariant::whereIn('id', $validVariants)
                        ->update(['is_active' => false]);
                    break;

                case 'update_prices':
                    $data = $request->data;
                    $updateData = [];

                    if (isset($data['retail_price'])) {
                        $updateData['retail_price'] = (float) $data['retail_price'];
                    }
                    if (isset($data['purchase_price'])) {
                        $updateData['purchase_price'] = (float) $data['purchase_price'];
                    }
                    if (isset($data['old_price'])) {
                        $updateData['old_price'] = (float) $data['old_price'];
                    }

                    if (! empty($updateData)) {
                        $updated = ProductVariant::whereIn('id', $validVariants)
                            ->update($updateData);
                    }
                    break;

                case 'update_stock':
                    if (isset($request->data['stock_default'])) {
                        $newStock = (int) $request->data['stock_default'];
                        // Use Eloquent model updates to trigger ProductVariantObserver
                        // which fires StockUpdated event for marketplace sync
                        $variants = ProductVariant::whereIn('id', $validVariants)->get();
                        foreach ($variants as $variant) {
                            if ($variant->stock_default !== $newStock) {
                                $variant->update(['stock_default' => $newStock]);
                                $updated++;
                            }
                        }
                    }
                    break;

                case 'update_category':
                    if (isset($request->data['category_id'])) {
                        $productIds = ProductVariant::whereIn('id', $validVariants)
                            ->distinct()
                            ->pluck('product_id');

                        $updated = Product::whereIn('id', $productIds)
                            ->update(['category_id' => $request->data['category_id']]);
                    }
                    break;
            }

            DB::commit();

            Log::info('Bulk product update', [
                'user_id' => $request->user()->id,
                'action' => $request->action,
                'variant_ids' => $validVariants,
                'updated' => $updated,
            ]);

            return response()->json([
                'message' => 'Bulk update completed successfully.',
                'updated_count' => $updated,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk product update failed', [
                'user_id' => $request->user()->id,
                'action' => $request->action,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Bulk update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format variant options for export
     */
    protected function formatVariantOptions(ProductVariant $variant): string
    {
        $options = [];

        foreach ($variant->optionValues as $optionValue) {
            $options[] = $optionValue->value;
        }

        return implode(', ', $options);
    }
}
