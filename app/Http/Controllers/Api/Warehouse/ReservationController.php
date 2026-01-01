<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\StockReservation;
use App\Services\Warehouse\ReservationService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = StockReservation::query()
            ->with('sku:id,sku_code')
            ->where('company_id', $companyId);
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->reason) {
            $query->where('reason', $request->reason);
        }

        return $this->successResponse($query->orderByDesc('id')->limit(500)->get());
    }

    public function reserve(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string'],
            'source_type' => ['nullable', 'string'],
            'source_id' => ['nullable', 'integer'],
        ]);

        $companyId = Auth::user()?->company_id;
        $userId = Auth::id();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        try {
            $reservation = app(ReservationService::class)->reserve(
                $companyId,
                $data['warehouse_id'],
                $data['sku_id'],
                (float) $data['qty'],
                $data['reason'],
                $data['source_type'] ?? null,
                $data['source_id'] ?? null,
                $userId
            );
            return $this->successResponse($reservation);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'reserve_failed', null, 422);
        }
    }

    public function release($id)
    {
        $companyId = Auth::user()?->company_id;
        $user = Auth::user();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $reservation = StockReservation::where('company_id', $companyId)->findOrFail($id);

        $res = app(ReservationService::class)->release((int) $id, $companyId);
        return $this->successResponse($res);
    }

    public function consume($id)
    {
        $companyId = Auth::user()?->company_id;
        $user = Auth::user();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $reservation = StockReservation::where('company_id', $companyId)->findOrFail($id);

        $res = app(ReservationService::class)->consume((int) $id, $companyId);
        return $this->successResponse($res);
    }
}
