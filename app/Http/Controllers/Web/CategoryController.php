<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class CategoryController extends Controller
{
    /**
     * Страница управления категориями
     *
     * GET /products/categories
     */
    public function index(): View
    {
        return view('products.categories');
    }
}
