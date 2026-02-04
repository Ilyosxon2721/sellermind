<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDescriptionResource;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductDescriptionController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $descriptions = $product->descriptions()
            ->orderBy('marketplace')
            ->orderBy('language')
            ->orderByDesc('version')
            ->get();

        return response()->json([
            'descriptions' => ProductDescriptionResource::collection($descriptions),
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'marketplace' => ['required', 'string', 'in:uzum,wb,ozon,ym,universal'],
            'language' => ['required', 'string', 'in:ru,uz'],
            'title' => ['required', 'string', 'max:500'],
            'short_description' => ['nullable', 'string'],
            'full_description' => ['nullable', 'string'],
            'bullets' => ['nullable', 'array'],
            'bullets.*' => ['string'],
            'attributes' => ['nullable', 'array'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string'],
        ]);

        $description = ProductDescription::createNewVersion($product, $request->marketplace, $request->language, [
            'title' => $request->title,
            'short_description' => $request->short_description,
            'full_description' => $request->full_description,
            'bullets' => $request->bullets,
            'attributes' => $request->attributes,
            'keywords' => $request->keywords,
        ]);

        return response()->json([
            'description' => new ProductDescriptionResource($description),
        ], 201);
    }

    public function show(Request $request, Product $product, ProductDescription $description): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($description->product_id !== $product->id) {
            return response()->json(['message' => 'Описание не принадлежит этому товару.'], 404);
        }

        return response()->json([
            'description' => new ProductDescriptionResource($description),
        ]);
    }

    public function versions(Request $request, Product $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'marketplace' => ['required', 'string', 'in:uzum,wb,ozon,ym,universal'],
            'language' => ['required', 'string', 'in:ru,uz'],
        ]);

        $versions = $product->descriptions()
            ->where('marketplace', $request->marketplace)
            ->where('language', $request->language)
            ->orderByDesc('version')
            ->get();

        return response()->json([
            'versions' => ProductDescriptionResource::collection($versions),
        ]);
    }

    public function destroy(Request $request, Product $product, ProductDescription $description): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($description->product_id !== $product->id) {
            return response()->json(['message' => 'Описание не принадлежит этому товару.'], 404);
        }

        $description->delete();

        return response()->json([
            'message' => 'Описание удалено.',
        ]);
    }
}
