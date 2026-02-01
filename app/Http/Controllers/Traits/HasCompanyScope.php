<?php

declare(strict_types=1);

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Единый безопасный метод получения ID компании текущего пользователя.
 * Заменяет дублированные getCompanyId() в 12 контроллерах.
 */
trait HasCompanyScope
{
    /**
     * Получить ID компании текущего пользователя.
     * Abort 403 если пользователь не авторизован или не привязан к компании.
     */
    protected function getCompanyId(): int
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Пользователь не авторизован');
        }

        $companyId = $user->company_id ?? $user->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Компания пользователя не определена');
        }

        return (int) $companyId;
    }
}
