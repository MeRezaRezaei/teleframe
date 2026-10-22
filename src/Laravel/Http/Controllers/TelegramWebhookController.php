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
        } elseif (! $this->hostAuthenticatesForUs()) {
            // No secret configured, and the host has not said it fronts the
            // webhook with its own authentication.
            //
            // 503 rather than 403 on purpose: 403 means "your credential is
            // wrong", which would send the operator hunting for a bad secret
            // that does not exist, and Telegram would treat it as a permanent
            // rejection. 503 says the server is not ready, so Telegram backs
            // off and retries once the operator fixes the config.
            //
            // This is the difference between a webhook that is unauthenticated
            // by accident and one that is unauthenticated on purpose, and it
            // is the difference between an attacker being able to inject
            // fabricated updates into the host's pipeline and not being able to.
            return new JsonResponse([
                'error' => 'teleframe.webhook_secret is not configured, so this endpoint refuses unauthenticated updates. '
                    .'Set TELEGRAM_WEBHOOK_SECRET, or set TELEFRAME_WEBHOOK_REQUIRE_SECRET=false if something in front of '
                    .'the webhook already authenticates the caller.',
            ], 503);
        } else {
            // The operator opted out deliberately. Still say so, every time, at
            // error level: an unauthenticated update endpoint is a live
            // integrity risk, and it should never be a silent configuration.
            $this->warnUnauthenticated();
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

    /**
     * Has the host said this endpoint is already authenticated in front of us?
     *
     * The INVERSE of `teleframe.webhook_require_secret`, and named so the
     * inversion is impossible to miss — the first version of this returned the
     * require flag directly under this name, which silently made the two
     * behaviours swap places, and the test caught it by checking both.
     */
    private function hostAuthenticatesForUs(): bool
    {
        if (! function_exists('config')) {
            return false;
        }

        try {
            return ! (bool) config('teleframe.webhook_require_secret', true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function warnUnauthenticated(): void
    {
        try {
            if (! function_exists('logger')) {
                return;
            }

            logger()->error('teleframe.webhook.unauthenticated: TELEFRAME_WEBHOOK_REQUIRE_SECRET is false and no secret is set, '
                .'so anyone who knows this URL can inject fabricated updates into the host pipeline');
        } catch (\Throwable) {
            // The warning must never be what breaks the request.
        }
    }
}
