<?php

namespace App\Services\Warehouse;

use Illuminate\Support\Facades\DB;

class DocNumberService
{
    /**
     * Генерация уникального номера документа с блокировкой для предотвращения race condition
     */
    public function generate(int $companyId, string $type): string
    {
        return DB::transaction(function () use ($companyId, $type) {
            $prefix = strtoupper($type).'-'.now()->format('Ymd').'-';

            // Используем MAX номера с блокировкой для избежания дублей
            $lastDocNo = DB::table('inventory_documents')
                ->where('company_id', $companyId)
                ->where('doc_no', 'like', $prefix.'%')
                ->lockForUpdate()
                ->max('doc_no');

            if ($lastDocNo) {
                $lastSeq = (int) substr($lastDocNo, strlen($prefix));
                $seq = $lastSeq + 1;
            } else {
                $seq = 1;
            }

            return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
