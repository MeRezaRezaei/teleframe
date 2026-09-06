<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramUpdateReceived;

/**
 * Standard, low-overhead Telegram Webhook intake controller.
 * Validates secret tokens, dispatches TelegramUpdateReceived events, and responds with HTTP 200.
 */
class TelegramWebhookController
{
    /**
     * Handle the incoming Telegram Webhook update.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $configuredSecret = function_exists('config')
                ? (config('teleframe.webhook_secret') ?? config('teleframe.secret_token'))
                : getenv('TELEGRAM_WEBHOOK_SECRET');
        } catch (\Throwable) {
            $configuredSecret = getenv('TELEGRAM_WEBHOOK_SECRET');
        }

        if (!empty($configuredSecret)) {
            $receivedHeader = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (!$receivedHeader || !hash_equals((string)$configuredSecret, (string)$receivedHeader)) {
                return new JsonResponse(['error' => 'Invalid secret token'], 403);
            }
        }

        $payload = $request->json()->all();

        if (!empty($payload) && isset($payload['update_id'])) {
            try {
                $botToken = function_exists('config') ? config('teleframe.bot_token') : null;
            } catch (\Throwable) {
                $botToken = null;
            }
            TelegramUpdateReceived::dispatch($payload, $botToken);
        }

        return new JsonResponse(['ok' => true]);
    }
}
