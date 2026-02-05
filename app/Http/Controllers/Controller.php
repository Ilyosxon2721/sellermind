<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Экранировать спецсимволы LIKE для безопасного поиска
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
