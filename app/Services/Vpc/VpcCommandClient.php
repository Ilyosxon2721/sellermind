<?php

// file: app/Services/Vpc/VpcCommandClient.php

namespace App\Services\Vpc;

use App\Models\VpcAction;
use App\Models\VpcSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Клиент для отправки команд в VPC-сессию.
 *
 * @deprecated Модуль VPC не реализован — все команды записываются в БД, но не отправляются в реальную VM.
 *             Все методы (openUrl, click, type, scroll и т.д.) являются заглушками.
 */
class VpcCommandClient
{
    /**
     * Общий метод отправки команды в VPC.
     *
     * @deprecated Заглушка — команда записывается в БД (VpcAction), но не отправляется в реальную VM.
     *             Требуется интеграция с HTTP/WebSocket менеджером VM.
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

        // Заглушка: команда НЕ отправляется в реальную VM, только записывается в БД
        Log::warning('VpcCommandClient::sendCommand() — команда записана в БД, но не отправлена в VM (модуль VPC не реализован)', [
            'session_id' => $session->id,
            'action_type' => $actionType,
            'has_endpoint' => ! empty($session->endpoint),
            'payload' => $payload,
        ]);

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
