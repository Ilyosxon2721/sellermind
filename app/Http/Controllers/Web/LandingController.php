<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Контроллер для локализованных лендинг-страниц
 */
final class LandingController extends Controller
{
    /**
     * Главная страница с выбором локали
     */
    public function home(string $locale): View
    {
        App::setLocale($locale);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('welcome', compact('plans'));
    }

    /**
     * Локализованная страница входа
     */
    public function login(string $locale): View
    {
        App::setLocale($locale);

        return view('pages.login');
    }

    /**
     * Локализованная страница регистрации
     */
    public function register(string $locale): View
    {
        App::setLocale($locale);

        return view('pages.register');
    }
}
