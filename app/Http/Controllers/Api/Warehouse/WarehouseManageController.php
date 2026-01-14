<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseManageController extends Controller
{
    use ApiResponder;

    /**
     * Get company ID with fallback to companies relationship
     */
    private function getCompanyId(): ?int
    {
        $user = auth('web')->user() ?? Auth::user();
        if (!$user) {
            return null;
        }
        return $user->company_id ?? $user->companies()->first()?->id;
    }

    public function index()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $items = Warehouse::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->successResponse($items);
    }

    public function show($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $warehouse = Warehouse::where('company_id', $companyId)->findOrFail($id);

        return $this->successResponse($warehouse);
    }

    public function store(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'address_comment' => ['nullable', 'string', 'max:1000'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'external_code' => ['nullable', 'string', 'max:255'],
            'meta_json' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (!empty($data['is_default'])) {
            Warehouse::where('company_id', $companyId)->update(['is_default' => false]);
        }

        $wh = Warehouse::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'address_comment' => $data['address_comment'] ?? null,
            'comment' => $data['comment'] ?? null,
            'group_name' => $data['group_name'] ?? null,
            'external_code' => $data['external_code'] ?? null,
            'meta_json' => $data['meta_json'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->successResponse($wh);
    }

    public function update($id, Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $wh = Warehouse::where('company_id', $companyId)->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'address_comment' => ['nullable', 'string', 'max:1000'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'external_code' => ['nullable', 'string', 'max:255'],
            'meta_json' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (!empty($data['is_default'])) {
            Warehouse::where('company_id', $companyId)->update(['is_default' => false]);
        }

        $wh->update($data);

        return $this->successResponse($wh);
    }

    public function makeDefault($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $wh = Warehouse::where('company_id', $companyId)->findOrFail($id);
        Warehouse::where('company_id', $companyId)->update(['is_default' => false]);
        $wh->update(['is_default' => true]);

        return $this->successResponse($wh);
    }
}
