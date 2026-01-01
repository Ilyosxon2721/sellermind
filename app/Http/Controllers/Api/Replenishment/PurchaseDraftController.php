<?php

namespace App\Http\Controllers\Api\Replenishment;

use App\Http\Controllers\Controller;
use App\Services\Replenishment\PurchaseDraftService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseDraftController extends Controller
{
    use ApiResponder;

    public function __construct(protected PurchaseDraftService $service)
    {
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['nullable', 'numeric'],
        ]);

        try {
            $po = $this->service->createDraft(
                $companyId,
                (int) $data['warehouse_id'],
                $data['supplier_id'] ?? null,
                $data['items']
            );
            return $this->successResponse($po);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'purchase_draft_failed', null, 422);
        }
    }
}
