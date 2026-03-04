<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\ProductCategory;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ProductCategoryController extends Controller
{
    use ApiResponder;
    use HasCompanyScope;

    /**
     * Получить дерево категорий (корневые с вложенными до 2 уровней)
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->withCount('products')
                    ->orderBy('sort_order')
                    ->with(['children' => function ($query) {
                        $query->withCount('products')
                            ->orderBy('sort_order');
                    }]);
            }])
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse($categories);
    }

    /**
     * Получить плоский список всех категорий (для выпадающих списков)
     */
    public function flat(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->with('parent')
            ->orderBy('sort_order')
            ->get()
            ->map(function (ProductCategory $category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'full_path' => $category->getFullPath(),
                ];
            });

        return $this->successResponse($categories);
    }

    /**
     * Создать новую категорию
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Проверяем, что родительская категория принадлежит той же компании
        if (! empty($validated['parent_id'])) {
            $parent = ProductCategory::where('id', $validated['parent_id'])
                ->where('company_id', $companyId)
                ->first();

            if (! $parent) {
                return $this->errorResponse(
                    'Родительская категория не найдена или принадлежит другой компании',
                    'invalid_parent',
                    'parent_id',
                    422
                );
            }
        }

        // Генерируем уникальный slug
        $slug = $this->generateUniqueSlug($validated['name'], $companyId);

        $category = ProductCategory::create([
            'company_id' => $companyId,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->successResponse($category, ['message' => 'Категория создана']);
    }

    /**
     * Обновить категорию
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $category = ProductCategory::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (! $category) {
            return $this->errorResponse('Категория не найдена', 'not_found', null, 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Проверяем, что новая родительская категория принадлежит той же компании
        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            // Запрет на установку себя как родителя
            if ((int) $validated['parent_id'] === $category->id) {
                return $this->errorResponse(
                    'Категория не может быть родителем самой себя',
                    'circular_reference',
                    'parent_id',
                    422
                );
            }

            $parent = ProductCategory::where('id', $validated['parent_id'])
                ->where('company_id', $companyId)
                ->first();

            if (! $parent) {
                return $this->errorResponse(
                    'Родительская категория не найдена или принадлежит другой компании',
                    'invalid_parent',
                    'parent_id',
                    422
                );
            }

            // Проверяем, что новый родитель не является потомком текущей категории (циклическая ссылка)
            if ($this->isDescendantOf($validated['parent_id'], $category->id, $companyId)) {
                return $this->errorResponse(
                    'Невозможно установить подкатегорию как родительскую (циклическая ссылка)',
                    'circular_reference',
                    'parent_id',
                    422
                );
            }
        }

        // Если имя изменилось, перегенерируем slug
        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $companyId, $category->id);
        }

        $category->update($validated);

        return $this->successResponse($category->fresh(), ['message' => 'Категория обновлена']);
    }

    /**
     * Удалить категорию
     */
    public function destroy(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $category = ProductCategory::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (! $category) {
            return $this->errorResponse('Категория не найдена', 'not_found', null, 404);
        }

        // Проверяем наличие товаров в категории
        if ($category->products()->exists()) {
            return $this->errorResponse(
                'Невозможно удалить категорию с товарами',
                'has_products',
                null,
                422
            );
        }

        // Проверяем наличие подкатегорий
        if ($category->children()->exists()) {
            return $this->errorResponse(
                'Невозможно удалить категорию с подкатегориями',
                'has_children',
                null,
                422
            );
        }

        $category->delete();

        return $this->successResponse(null, ['message' => 'Категория удалена']);
    }

    /**
     * Сгенерировать уникальный slug в рамках компании
     */
    private function generateUniqueSlug(string $name, int $companyId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            $query = ProductCategory::where('company_id', $companyId)
                ->where('slug', $slug);

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Проверить, является ли категория потомком указанной категории
     */
    private function isDescendantOf(int $categoryId, int $ancestorId, int $companyId): bool
    {
        $current = ProductCategory::where('id', $categoryId)
            ->where('company_id', $companyId)
            ->first();

        // Обходим дерево вверх от потенциального родителя до корня
        $visited = [];
        while ($current) {
            if ($current->id === $ancestorId) {
                return true;
            }

            // Защита от бесконечного цикла в случае повреждённых данных
            if (in_array($current->id, $visited, true)) {
                break;
            }
            $visited[] = $current->id;

            if ($current->parent_id === null) {
                break;
            }

            $current = ProductCategory::where('id', $current->parent_id)
                ->where('company_id', $companyId)
                ->first();
        }

        return false;
    }
}
