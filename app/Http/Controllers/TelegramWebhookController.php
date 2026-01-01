<?php

namespace App\Http\Controllers;

use App\Telegram\TelegramBotHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramBotHandler $botHandler
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        $this->botHandler->handle($update);

        return response()->json(['ok' => true]);
    }
}
