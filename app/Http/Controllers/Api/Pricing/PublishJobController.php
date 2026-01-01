<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PricePublishJob;
use App\Services\Pricing\PricePublishService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublishJobController extends Controller
{
    use ApiResponder;

    public function __construct(protected PricePublishService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $q = PricePublishJob::byCompany($companyId);
        if ($request->status) $q->where('status', $request->status);
        if ($request->channel_code) $q->where('channel_code', $request->channel_code);
        return $this->successResponse($q->orderByDesc('id')->limit(200)->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $data = $request->validate([
            'scenario_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'sku_ids' => ['required', 'array'],
        ]);

        $job = $this->service->buildJob($companyId, $data['scenario_id'], $data['channel_code'], $data['sku_ids'], Auth::id());
        return $this->successResponse($job);
    }

    public function queue($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $job = $this->service->queue($id, $companyId);
        return $this->successResponse($job);
    }

    public function run($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $job = $this->service->run($id, $companyId);
        return $this->successResponse($job);
    }

    public function exportCsv($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) abort(403);

        $job = PricePublishJob::byCompany($companyId)->findOrFail($id);
        $items = $job->payload_json['items'] ?? [];

        $response = new StreamedResponse(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['sku_id', 'recommended_price']);
            foreach ($items as $item) {
                fputcsv($handle, [$item['sku_id'], $item['recommended_price']]);
            }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="prices.csv"');
        return $response;
    }
}
