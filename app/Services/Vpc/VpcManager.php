<?php

// file: app/Services/Vpc/VpcManager.php

namespace App\Services\Vpc;

use App\Models\User;
use App\Models\VpcSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Менеджер VPC-сессий.
 *
 * @deprecated Модуль VPC не реализован — все методы являются заглушками.
 *             VM не запускается, endpoint фиктивный. Не использовать в продакшене.
 */
class VpcManager
{
    /**
     * Создать VPC-сессию (БЕЗ реального запуска VM).
     * На этом этапе считаем, что реальная VM будет подниматься внешней системой.
     *
     * @deprecated Заглушка — реальная VM не создаётся
     */
    public function createSession(User $user, array $data = []): VpcSession
    {
        Log::warning('VpcManager::createSession() вызван — модуль VPC не реализован, VM не запускается', [
            'user_id' => $user->id,
        ]);

        return VpcSession::create([
            'user_id' => $user->id,
            'company_id' => $data['company_id'] ?? null,
            'agent_task_id' => $data['agent_task_id'] ?? null,
            'name' => $data['name'] ?? 'Новая VPC-сессия',
            'status' => VpcSession::STATUS_CREATING,
            'control_mode' => VpcSession::CONTROL_AGENT,
            'endpoint' => null, // TODO: заполнить при реальном запуске VM
            'display_token' => Str::random(40),
            'started_at' => null,
            'stopped_at' => null,
            'last_activity_at' => null,
        ]);
    }

    /**
     * Пометить VPC как готовый к работе.
     */
    public function markSessionReady(VpcSession $session): void
    {
        $session->update([
            'status' => VpcSession::STATUS_READY,
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Пометить VPC как запущенный.
     * Используется после того, как внешняя система подняла VM
     * и вернула endpoint (например, ws://host:port).
     */
    public function markSessionStarted(VpcSession $session, string $endpoint): void
    {
        $session->update([
            'status' => VpcSession::STATUS_RUNNING,
            'endpoint' => $endpoint,
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Запустить сессию (симуляция для разработки).
     *
     * @deprecated Заглушка — реальная VM не запускается. Endpoint фиктивный (ws://localhost:5900).
     *             В продакшене требуется интеграция с внешней системой управления VM.
     */
    public function startSession(VpcSession $session): void
    {
        Log::warning('VpcManager::startSession() вызван — VM не запускается, endpoint фиктивный', [
            'session_id' => $session->id,
        ]);

        // Для разработки - сразу помечаем как running с заглушкой endpoint
        $session->update([
            'status' => VpcSession::STATUS_RUNNING,
            'endpoint' => 'ws://localhost:5900', // Заглушка
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Поставить сессию на паузу.
     */
    public function pauseSession(VpcSession $session): void
    {
        $session->update([
            'status' => VpcSession::STATUS_PAUSED,
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Возобновить сессию после паузы.
     */
    public function resumeSession(VpcSession $session): void
    {
        $session->update([
            'status' => VpcSession::STATUS_RUNNING,
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Остановить сессию (логически).
     *
     * @deprecated Заглушка — реальная VM не останавливается. Только обновляется статус в БД.
     */
    public function stopSession(VpcSession $session): void
    {
        Log::warning('VpcManager::stopSession() вызван — VM не останавливается, только обновление статуса в БД', [
            'session_id' => $session->id,
        ]);

        $session->update([
            'status' => VpcSession::STATUS_STOPPED,
            'stopped_at' => Carbon::now(),
        ]);
    }

    /**
     * Пометить сессию как ошибочную.
     */
    public function markSessionError(VpcSession $session, ?string $error = null): void
    {
        $session->update([
            'status' => VpcSession::STATUS_ERROR,
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Установить режим управления.
     */
    public function setControlMode(VpcSession $session, string $mode): void
    {
        if (! in_array($mode, [VpcSession::CONTROL_AGENT, VpcSession::CONTROL_USER, VpcSession::CONTROL_PAUSED])) {
            throw new \InvalidArgumentException("Invalid control mode: {$mode}");
        }

        $session->update([
            'control_mode' => $mode,
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Обновить время последней активности.
     */
    public function touchActivity(VpcSession $session): void
    {
        $session->update([
            'last_activity_at' => Carbon::now(),
        ]);
    }
}
