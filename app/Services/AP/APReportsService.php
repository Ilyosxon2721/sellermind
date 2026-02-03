<?php

namespace App\Services\AP;

use App\Models\AP\SupplierInvoice;
use Illuminate\Support\Carbon;

class APReportsService
{
    public function aging(int $companyId, ?string $asOfDate = null): array
    {
        $date = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();
        $invoices = SupplierInvoice::byCompany($companyId)
            ->where('amount_outstanding', '>', 0)
            ->get(['supplier_id', 'amount_outstanding', 'due_date']);

        $buckets = [];
        foreach ($invoices as $inv) {
            $due = $inv->due_date ? Carbon::parse($inv->due_date) : $date;
            $diff = $due->diffInDays($date, false);
            $bucket = match (true) {
                $diff <= 7 => '0-7',
                $diff <= 30 => '8-30',
                $diff <= 60 => '31-60',
                default => '60+',
            };
            $buckets[$inv->supplier_id][$bucket] = ($buckets[$inv->supplier_id][$bucket] ?? 0) + $inv->amount_outstanding;
        }

        return $buckets;
    }

    public function overdue(int $companyId, ?string $asOfDate = null)
    {
        $date = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();

        return SupplierInvoice::byCompany($companyId)
            ->where('amount_outstanding', '>', 0)
            ->whereDate('due_date', '<', $date)
            ->get();
    }

    public function calendar(int $companyId, string $from, string $to)
    {
        return SupplierInvoice::byCompany($companyId)
            ->where('amount_outstanding', '>', 0)
            ->whereBetween('due_date', [$from, $to])
            ->orderBy('due_date')
            ->get();
    }
}
