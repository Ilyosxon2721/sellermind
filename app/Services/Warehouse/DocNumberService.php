<?php

namespace App\Services\Warehouse;

use Illuminate\Support\Facades\DB;

class DocNumberService
{
    public function generate(int $companyId, string $type): string
    {
        $prefix = strtoupper($type).'-'.now()->format('Ymd').'-';
        $seq = DB::table('inventory_documents')
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->count() + 1;

        return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
