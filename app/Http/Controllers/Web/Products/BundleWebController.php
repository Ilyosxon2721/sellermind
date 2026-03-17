<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Web контроллер для отображения страниц комплектов
 */
final class BundleWebController extends Controller
{
    /**
     * Страница списка комплектов
     */
    public function index(Request $request): View
    {
        return view('bundles.index');
    }

    /**
     * Страница создания комплекта
     */
    public function create(Request $request): View
    {
        return view('bundles.create');
    }

    /**
     * Страница редактирования комплекта
     */
    public function edit(Request $request, int $id): View
    {
        return view('bundles.edit', ['bundleId' => $id]);
    }
}
