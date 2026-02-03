<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalOption;
use App\Models\GlobalOptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalOptionValueController extends Controller
{
    /**
     * Store a new custom size or color for the company.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:size,color'],
            'value' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'max:7'],
        ]);

        $user = $request->user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 403);
        }

        $type = $request->input('type');
        $code = $request->input('code');

        // Get the global option for size or color
        $globalOption = GlobalOption::whereNull('company_id')
            ->where('code', $type)
            ->first();

        if (! $globalOption) {
            return response()->json(['error' => 'Global option not found'], 404);
        }

        // Check if value with this code already exists (either globally or for this company)
        $existingGlobal = GlobalOptionValue::where('global_option_id', $globalOption->id)
            ->whereNull('company_id')
            ->where('code', $code)
            ->exists();

        if ($existingGlobal) {
            return response()->json(['error' => 'Value with this code already exists globally'], 409);
        }

        $existingCompany = GlobalOptionValue::where('global_option_id', $globalOption->id)
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->exists();

        if ($existingCompany) {
            return response()->json(['error' => 'Value with this code already exists for your company'], 409);
        }

        // Get max sort_order
        $maxSortOrder = GlobalOptionValue::where('global_option_id', $globalOption->id)
            ->max('sort_order') ?? 0;

        // Create the new value
        $optionValue = GlobalOptionValue::create([
            'company_id' => $companyId,
            'global_option_id' => $globalOption->id,
            'value' => $request->input('value'),
            'code' => $code,
            'color_hex' => $request->input('color_hex'),
            'sort_order' => $maxSortOrder + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $optionValue->id,
                'value' => $optionValue->value,
                'code' => $optionValue->code,
                'color_hex' => $optionValue->color_hex,
            ],
        ], 201);
    }

    /**
     * Get all sizes (global + company specific).
     */
    public function sizes(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->company_id;

        $globalOption = GlobalOption::whereNull('company_id')
            ->where('code', 'size')
            ->first();

        if (! $globalOption) {
            return response()->json(['data' => []]);
        }

        $query = GlobalOptionValue::where('global_option_id', $globalOption->id)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('sort_order');

        return response()->json([
            'data' => $query->get()->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'code' => $v->code,
                'is_custom' => $v->company_id !== null,
            ]),
        ]);
    }

    /**
     * Get all colors (global + company specific).
     */
    public function colors(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->company_id;

        $globalOption = GlobalOption::whereNull('company_id')
            ->where('code', 'color')
            ->first();

        if (! $globalOption) {
            return response()->json(['data' => []]);
        }

        $query = GlobalOptionValue::where('global_option_id', $globalOption->id)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('sort_order');

        return response()->json([
            'data' => $query->get()->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'code' => $v->code,
                'color_hex' => $v->color_hex,
                'is_custom' => $v->company_id !== null,
            ]),
        ]);
    }
}
