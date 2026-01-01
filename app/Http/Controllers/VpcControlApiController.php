<?php
// file: app/Http/Controllers/VpcControlApiController.php

namespace App\Http\Controllers;

use App\Models\VpcSession;
use App\Models\VpcAction;
use App\Services\Vpc\VpcCommandClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VpcControlApiController extends Controller
{
    public function __construct(
        protected VpcCommandClient $commandClient
    ) {}

    /**
     * Отправить действие в VPC (от пользователя).
     */
    public function sendAction(VpcSession $vpcSession, Request $request): JsonResponse
    {
        $this->authorizeSession($vpcSession);

        // Проверяем режим управления
        if ($vpcSession->control_mode !== VpcSession::CONTROL_USER) {
            return response()->json([
                'error' => 'Сессия не в режиме ручного управления.',
                'current_mode' => $vpcSession->control_mode,
            ], 400);
        }

        // Проверяем статус сессии
        if (!$vpcSession->isRunning()) {
            return response()->json([
                'error' => 'Сессия не запущена.',
                'current_status' => $vpcSession->status,
            ], 400);
        }

        $data = $request->validate([
            'action_type' => ['required', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
        ]);

        $action = $this->commandClient->sendCommand(
            $vpcSession,
            VpcAction::SOURCE_USER,
            $data['action_type'],
            $data['payload'] ?? []
        );

        return response()->json([
            'status' => 'ok',
            'action_id' => $action->id,
        ]);
    }

    /**
     * Получить статус сессии.
     */
    public function getStatus(VpcSession $vpcSession): JsonResponse
    {
        $this->authorizeSession($vpcSession);

        return response()->json([
            'id' => $vpcSession->id,
            'status' => $vpcSession->status,
            'control_mode' => $vpcSession->control_mode,
            'endpoint' => $vpcSession->endpoint,
            'display_token' => $vpcSession->display_token,
            'last_activity_at' => $vpcSession->last_activity_at,
        ]);
    }

    /**
     * Получить последние действия.
     */
    public function getActions(VpcSession $vpcSession, Request $request): JsonResponse
    {
        $this->authorizeSession($vpcSession);

        $limit = min($request->get('limit', 20), 100);
        $afterId = $request->get('after_id');

        $query = $vpcSession->actions()->orderBy('id', 'desc');

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        $actions = $query->limit($limit)->get()->reverse()->values();

        return response()->json([
            'actions' => $actions->map(function ($action) {
                return [
                    'id' => $action->id,
                    'source' => $action->source,
                    'action_type' => $action->action_type,
                    'payload' => $action->payload,
                    'created_at' => $action->created_at,
                ];
            }),
        ]);
    }

    /**
     * Переключить режим управления через API.
     */
    public function setControlMode(VpcSession $vpcSession, Request $request): JsonResponse
    {
        $this->authorizeSession($vpcSession);

        $data = $request->validate([
            'mode' => ['required', 'in:AGENT_CONTROL,USER_CONTROL,PAUSED'],
        ]);

        $vpcSession->update([
            'control_mode' => $data['mode'],
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'control_mode' => $data['mode'],
        ]);
    }

    /**
     * Проверить, что сессия принадлежит текущему пользователю.
     */
    protected function authorizeSession(VpcSession $session): void
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }
    }
}
