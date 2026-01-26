<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\StockReservation;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\OzonOrder;
use App\Services\Warehouse\ReservationService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    use ApiResponder;

    /**
     * Get company ID with fallback to companies relationship
     */
    private function getCompanyId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return $user->company_id ?? $user->companies()->first()?->id;
    }

    public function index(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = StockReservation::query()
            ->with(['sku.product.images', 'sku.productVariant.product.images', 'warehouse'])
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

        $reservations = $query->orderByDesc('id')->limit(500)->get();

        // Load order information for marketplace orders
        $reservations = $reservations->map(function ($reservation) {
            $data = $reservation->toArray();

            // Add product image and name
            $data['product_image'] = null;
            $data['product_name'] = null;

            // Try to get product info from different paths
            $product = null;
            $variant = $reservation->sku?->productVariant;

            // Path 1: sku -> product
            if ($reservation->sku?->product) {
                $product = $reservation->sku->product;
            }
            // Path 2: sku -> productVariant -> product
            elseif ($variant?->product) {
                $product = $variant->product;
            }

            if ($product) {
                $data['product_name'] = $product->name;
                $image = $product->images->first();
                if ($image) {
                    $data['product_image'] = $image->url ?? $image->path ?? null;
                }
            }

            // Fallback: use variant name if no product name
            if (!$data['product_name'] && $variant) {
                $data['product_name'] = $variant->name ?? $variant->sku ?? null;
            }

            // Add order info for marketplace orders
            $data['order_number'] = null;
            $data['order_date'] = null;
            $data['order_status'] = null;
            $data['order_status_normalized'] = null;
            $data['marketplace'] = null;
            $data['marketplace_account_name'] = null;

            if ($reservation->source_type === 'marketplace_order' && $reservation->source_id) {
                $order = $this->getMarketplaceOrder($reservation->reason, $reservation->source_id);
                if ($order) {
                    $data['order_number'] = $order->external_order_id ?? $order->order_id ?? null;
                    $data['order_date'] = $order->ordered_at ?? $order->created_at;
                    $data['order_status'] = $order->uzum_status ?? $order->wb_status ?? $order->ozon_status ?? $order->status ?? null;
                    $data['order_status_normalized'] = $order->status_normalized ?? $order->status ?? null;
                    $data['marketplace'] = $this->extractMarketplace($reservation->reason);

                    // Get account name
                    $account = $order->account ?? null;
                    $data['marketplace_account_name'] = $account?->name ?? $account?->shop_name ?? null;
                }
            }

            return $data;
        });

        return $this->successResponse($reservations);
    }

    /**
     * Get marketplace order by source ID based on reason string
     */
    private function getMarketplaceOrder(string $reason, int $sourceId)
    {
        $marketplace = $this->extractMarketplace($reason);

        return match ($marketplace) {
            'uzum' => UzumOrder::find($sourceId),
            'wb', 'wildberries' => WbOrder::find($sourceId),
            'ozon' => OzonOrder::find($sourceId),
            default => null,
        };
    }

    /**
     * Extract marketplace name from reason string
     */
    private function extractMarketplace(string $reason): ?string
    {
        if (preg_match('/marketplace\s*order:\s*(\w+)/i', $reason, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
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

        $companyId = $this->getCompanyId();
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
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $reservation = StockReservation::where('company_id', $companyId)->findOrFail($id);

        $res = app(ReservationService::class)->release((int) $id, $companyId);
        return $this->successResponse($res);
    }

    public function consume($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $reservation = StockReservation::where('company_id', $companyId)->findOrFail($id);

        $res = app(ReservationService::class)->consume((int) $id, $companyId);
        return $this->successResponse($res);
    }
}
