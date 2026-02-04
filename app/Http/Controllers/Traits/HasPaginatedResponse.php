<?php

declare(strict_types=1);

namespace App\Http\Controllers\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait HasPaginatedResponse
{
    /**
     * Получить количество элементов на страницу из запроса
     */
    protected function getPerPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min((int) $request->get('per_page', $default), $max);
    }

    /**
     * Получить мета-данные пагинации
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
