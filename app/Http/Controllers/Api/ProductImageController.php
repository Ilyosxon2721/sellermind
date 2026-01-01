<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $images = $product->images()->orderByDesc('is_primary')->orderByDesc('created_at')->get();

        return response()->json([
            'images' => ProductImageResource::collection($images),
        ]);
    }

    public function upload(Request $request, Product $product): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:10240'], // max 10MB
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('image')->store("products/{$product->id}", 'public');

        $image = ProductImage::create([
            'product_id' => $product->id,
            'type' => 'original',
            'quality' => 'high',
            'url' => Storage::url($path),
            'source' => 'upload',
            'is_primary' => $request->is_primary ?? false,
        ]);

        if ($request->is_primary) {
            $image->makePrimary();
        }

        return response()->json([
            'image' => new ProductImageResource($image),
        ], 201);
    }

    public function generate(Request $request, Product $product, AIService $aiService): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'quality' => ['nullable', 'in:low,medium,high'],
            'count' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $company = $product->company;
        $user = $request->user();

        $results = $aiService->generateImages(
            $request->prompt,
            $request->quality ?? 'medium',
            $request->count ?? 1,
            $company->id,
            $user->id
        );

        $images = [];
        foreach ($results as $url) {
            $image = ProductImage::create([
                'product_id' => $product->id,
                'type' => 'generated',
                'quality' => $request->quality ?? 'medium',
                'url' => $url,
                'prompt' => $request->prompt,
                'source' => 'generated',
                'is_primary' => false,
            ]);
            $images[] = $image;
        }

        return response()->json([
            'images' => ProductImageResource::collection($images),
        ], 201);
    }

    public function setPrimary(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Изображение не принадлежит этому товару.'], 404);
        }

        $image->makePrimary();

        return response()->json([
            'image' => new ProductImageResource($image->fresh()),
        ]);
    }

    public function destroy(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($product->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Изображение не принадлежит этому товару.'], 404);
        }

        // Delete file if it's an upload
        if ($image->source === 'upload' && $image->url) {
            $path = str_replace('/storage/', '', $image->url);
            Storage::disk('public')->delete($path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Изображение удалено.',
        ]);
    }

    /**
     * Upload image for new product (before product is saved)
     * Returns temporary path that can be used when creating the product
     */
    public function uploadTemp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => ['required', 'image', 'max:10240'], // max 10MB
            ], [
                'image.required' => 'Файл изображения обязателен',
                'image.image' => 'Файл должен быть изображением (jpg, png, gif, webp)',
                'image.max' => 'Максимальный размер файла 10 МБ',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Требуется авторизация. Пожалуйста, перезагрузите страницу.'], 401);
            }

            $companyId = $user->company_id;
            if (!$companyId) {
                return response()->json(['message' => 'Компания не найдена. Выберите компанию в настройках.'], 403);
            }

            // Store in temp folder with company ID prefix
            $path = $request->file('image')->store("products/temp/{$companyId}", 'public');
            
            if (!$path) {
                return response()->json(['message' => 'Не удалось сохранить файл. Проверьте права доступа.'], 500);
            }

            return response()->json([
                'path' => Storage::url($path),
                'url' => Storage::url($path),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при загрузке: ' . $e->getMessage(),
            ], 500);
        }
    }
}
