<?php
// file: app/Services/Vpc/VpcCommandClient.php

namespace App\Services\Vpc;

use App\Models\VpcSession;
use App\Models\VpcAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpcCommandClient
{
    /**
     * Общий метод отправки команды в VPC.
     * ВНИМАНИЕ: здесь пока только ЗАГЛУШКА.
     *
     * TODO: заменить на реальный HTTP/WebSocket-вызов менеджеру VM.
     */
    public function sendCommand(VpcSession $session, string $source, string $actionType, array $payload = []): VpcAction
    {
        // 1. Логируем действие
        $action = VpcAction::create([
            'vpc_session_id' => $session->id,
            'source' => $source,
            'action_type' => $actionType,
            'payload' => $payload,
            'created_at' => now(),
        ]);

        // 2. Обновляем время активности сессии
        $session->update(['last_activity_at' => now()]);

        // 3. Если endpoint не задан — ничего не делаем (заглушка)
        if (empty($session->endpoint)) {
            Log::info('VPC Command (stub mode)', [
                'session_id' => $session->id,
                'action_type' => $actionType,
                'payload' => $payload,
            ]);
            // TODO: интеграция с реальным VPC-менеджером
            return $action;
        }

        // 4. Отправка команды во внешнюю систему
        // TODO: Раскомментировать и настроить при интеграции с реальной VM
        /*
        try {
            $response = Http::timeout(30)->post($session->endpoint . '/command', [
                'action_type' => $actionType,
                'payload' => $payload,
                'token' => $session->display_token,
            ]);

            if ($response->failed()) {
                Log::error('VPC Command failed', [
                    'session_id' => $session->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('VPC Command exception', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
        */

        return $action;
    }

    /**
     * Открыть URL в браузере VPC.
     */
    public function openUrl(VpcSession $session, string $source, string $url): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_OPEN_URL, [
            'url' => $url,
        ]);
    }

    /**
     * Кликнуть по координатам.
     */
    public function click(VpcSession $session, string $source, int $x, int $y, string $button = 'left'): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_CLICK, [
            'x' => $x,
            'y' => $y,
            'button' => $button,
        ]);
    }

    /**
     * Ввести текст.
     */
    public function type(VpcSession $session, string $source, string $text): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_TYPE, [
            'text' => $text,
        ]);
    }

    /**
     * Прокрутить страницу.
     */
    public function scroll(VpcSession $session, string $source, int $deltaX, int $deltaY): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_SCROLL, [
            'delta_x' => $deltaX,
            'delta_y' => $deltaY,
        ]);
    }

    /**
     * Сделать скриншот.
     */
    public function screenshot(VpcSession $session, string $source): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_SCREENSHOT, []);
    }

    /**
     * Нажать клавишу.
     */
    public function keyPress(VpcSession $session, string $source, string $key, array $modifiers = []): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_KEY_PRESS, [
            'key' => $key,
            'modifiers' => $modifiers, // ['ctrl', 'shift', 'alt']
        ]);
    }

    /**
     * Переместить мышь.
     */
    public function mouseMove(VpcSession $session, string $source, int $x, int $y): VpcAction
    {
        return $this->sendCommand($session, $source, VpcAction::ACTION_MOUSE_MOVE, [
            'x' => $x,
            'y' => $y,
        ]);
    }
}
