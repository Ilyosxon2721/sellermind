<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOwner
{
    /**
     * Проверяет, что текущий пользователь является владельцем своей компании.
     *
     * Используется для защиты действий, доступных только владельцу:
     * управление подпиской, удаление компании, управление ролями и т.д.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = $request->user();

            if (! $user || ! $user->company_id) {
                return $this->accessDenied($request);
            }

            $companyId = (int) $user->company_id;

            if (! $user->isOwnerOf($companyId)) {
                return $this->accessDenied($request);
            }

            return $next($request);
        } catch (\Exception $e) {
            \Log::error('EnsureCompanyOwner middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            // В случае ошибки — запрещаем доступ (fail-closed для безопасности)
            return $this->accessDenied($request);
        }
    }

    /**
     * Вернуть ответ с отказом в доступе (403)
     */
    protected function accessDenied(Request $request): Response
    {
        $message = 'Только владелец компании может выполнить это действие.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'error' => 'forbidden',
            ], 403);
        }

        return redirect()
            ->back()
            ->with('error', $message);
    }
}
