<?php

// file: app/Services/Vpc/VpcManager.php

namespace App\Services\Vpc;

use App\Models\User;
use App\Models\VpcSession;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VpcManager
{
    /**
     * Создать VPC-сессию (БЕЗ реального запуска VM).
     * На этом этапе считаем, что реальная VM будет подниматься внешней системой.
     */
    public function createSession(User $user, array $data = []): VpcSession
    {
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
     * TODO: В продакшене здесь будет вызов внешней системы для запуска VM.
     */
    public function startSession(VpcSession $session): void
    {
        // TODO: Отправить запрос во внешнюю систему на запуск VM
        // $response = Http::post('vm-manager.local/api/start', [...]);
        // $endpoint = $response['endpoint'];

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
     * Реальный stop VM будет делать внешняя система.
     */
    public function stopSession(VpcSession $session): void
    {
        // TODO: Отправить внешней системе команду на остановку VM
        // Http::post('vm-manager.local/api/stop', ['session_id' => $session->id]);

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
