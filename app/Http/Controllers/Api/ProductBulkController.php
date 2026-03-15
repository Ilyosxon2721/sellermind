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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        // Заголовки колонок
        $headers = [
            'ID товара',
            'Название',
            'Артикул',
            'Категория',
            'ID варианта',
            'SKU',
            'Штрихкод',
            'Закупочная цена',
            'Валюта закупки',
            'Розничная цена',
            'Старая цена',
            'Остаток',
            'Активен',
            'Опции варианта',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Товары');

        // Записываем заголовки
        foreach ($headers as $col => $header) {
            $cell = $sheet->setCellValue([$col + 1, 1], $header);
        }

        // Стиль заголовков: жирный шрифт
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        // Записываем данные
        $row = 2;
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $sheet->setCellValue([1, $row], $product->id);
                $sheet->setCellValue([2, $row], $product->name);
                $sheet->setCellValue([3, $row], $product->article);
                $sheet->setCellValue([4, $row], $product->category?->name ?? '');
                $sheet->setCellValue([5, $row], $variant->id);
                $sheet->setCellValue([6, $row], $variant->sku);
                $sheet->setCellValue([7, $row], $variant->barcode ?? '');
                $sheet->setCellValue([8, $row], $variant->purchase_price ?? 0);
                $sheet->setCellValue([9, $row], $variant->purchase_price_currency ?? 'UZS');
                $sheet->setCellValue([10, $row], $variant->price_default ?? 0);
                $sheet->setCellValue([11, $row], $variant->old_price_default ?? 0);
                $sheet->setCellValue([12, $row], $variant->stock_default ?? 0);
                $sheet->setCellValue([13, $row], $variant->is_active ? 'Да' : 'Нет');
                $sheet->setCellValue([14, $row], $this->formatVariantOptions($variant));
                $row++;
            }
        }

        // Автоширина колонок
        foreach (range('A', $lastCol) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Сохраняем во временный файл
        $filename = 'products_export_'.now()->format('Y-m-d_His').'.xlsx';
        $filePath = storage_path('app/temp/'.$filename);

        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Preview bulk import from Excel
     */
    public function previewImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $csvData = $this->parseImportFile($file);

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
            $newPurchaseCurrency = isset($row[8]) && in_array(strtoupper(trim($row[8])), ['UZS', 'USD', 'RUB', 'EUR', 'KZT']) ? strtoupper(trim($row[8])) : null;
            $newRetailPrice = $row[9] ?? null;
            $newOldPrice = $row[10] ?? null;
            $newStock = $row[11] ?? null;
            $isActive = isset($row[12]) && trim($row[12]) !== '' ? in_array(strtolower(trim($row[12])), ['yes', 'да', '1', 'true']) : null;

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
            if ($newPurchaseCurrency !== null && ($variant->purchase_price_currency ?? 'UZS') !== $newPurchaseCurrency) {
                $changes['purchase_price_currency'] = [
                    'old' => $variant->purchase_price_currency ?? 'UZS',
                    'new' => $newPurchaseCurrency,
                ];
            }
            if ($newRetailPrice !== null && $newRetailPrice !== '' && $variant->price_default != $newRetailPrice) {
                $changes['price_default'] = [
                    'old' => $variant->price_default,
                    'new' => (float) $newRetailPrice,
                ];
            }
            if ($newOldPrice !== null && $newOldPrice !== '' && $variant->old_price_default != $newOldPrice) {
                $changes['old_price_default'] = [
                    'old' => $variant->old_price_default,
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
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');

        // Конвертируем любой формат (XLSX/CSV) в CSV для джоба
        $tempFilePath = storage_path('app/temp/import_'.uniqid().'.csv');
        if (! file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0755, true);
        }

        $rows = $this->parseImportFile($file);
        // Записываем как CSV с точкой с запятой
        $fp = fopen($tempFilePath, 'w');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }
        fclose($fp);

        // Убираем заголовок — джоб сам удалит первую строку

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

                    if (isset($data['price_default'])) {
                        $updateData['price_default'] = (float) $data['price_default'];
                    }
                    // Для обратной совместимости с фронтендом
                    if (isset($data['retail_price'])) {
                        $updateData['price_default'] = (float) $data['retail_price'];
                    }
                    if (isset($data['purchase_price'])) {
                        $updateData['purchase_price'] = (float) $data['purchase_price'];
                    }
                    if (isset($data['purchase_price_currency']) && in_array($data['purchase_price_currency'], ['UZS', 'USD', 'RUB', 'EUR', 'KZT'])) {
                        $updateData['purchase_price_currency'] = $data['purchase_price_currency'];
                    }
                    if (isset($data['old_price_default'])) {
                        $updateData['old_price_default'] = (float) $data['old_price_default'];
                    }
                    // Для обратной совместимости
                    if (isset($data['old_price'])) {
                        $updateData['old_price_default'] = (float) $data['old_price'];
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
     * Парсинг файла импорта (поддержка CSV и XLSX)
     *
     * @return array<int, array<int, string>>
     */
    protected function parseImportFile(\Illuminate\Http\UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseXlsxFile($file->getPathname());
        }

        // CSV / TXT
        return $this->parseCsvFile($file->getPathname());
    }

    /**
     * Парсинг XLSX файла через PhpSpreadsheet
     */
    protected function parseXlsxFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = (string) ($cell->getValue() ?? '');
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Парсинг CSV файла (разделитель — точка с запятой)
     */
    protected function parseCsvFile(string $path): array
    {
        $rows = array_map(function ($row) {
            return str_getcsv($row, ';');
        }, file($path));

        // Удаляем BOM
        if (isset($rows[0][0])) {
            $rows[0][0] = str_replace("\xEF\xBB\xBF", '', $rows[0][0]);
        }

        return $rows;
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
