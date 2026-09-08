<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\BotMap\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\BotMap\BotMap;
use RuntimeException;
use Throwable;

/**
 * Outbound HTTP exposure of registered bots (Phase 5f): POST
 * /telegram/bot-map/{bot}/{method} invokes any Bot API method on a registered
 * bot and returns the BotClient response as JSON.
 *
 * Auth is a simple, versioned HMAC header — `X-BotMap-Signature` — computed as
 * HMAC-SHA256 over "bot\nmethod\nbody" with the configured shared secret. The
 * route params are bound into the MAC so a captured signature can never be
 * replayed onto a different bot or method (the webhook secret precedent, in
 * the webhook-controller style). The secret comes from config
 * teleframe.botmap.secret or env TELEFRAME_BOTMAP_SECRET; a controller with no
 * secret answers 500 so a misconfigured, unauthenticated endpoint can never
 * silently ship.
 *
 * Plain-PHP constructible for tests: pass a BotMap and the secret directly.
 * Laravel wiring (container-bound BotMap + macro registration) is documented
 * in docs/botmap.md — this file never hard-calls the container.
 */
final class BotMapController
{
    public const AUTH_HEADER = 'X-BotMap-Signature';

    public const DEFAULT_URI = 'telegram/bot-map/{bot}/{method}';

    /**
     * Register the `Route::telegramBotApi()` macro, mirroring how the package
     * registers `Route::telegramWebhook` (macro on the Router instance, caller
     * registers it — the provider is not touched).
     */
    public static function macro(Router $router, string $uri = self::DEFAULT_URI): void
    {
        $router->macro('telegramBotApi', function (string $endpoint = BotMapController::DEFAULT_URI) use ($router): Route {
            return $router->post($endpoint, BotMapController::class);
        });
    }

    public function __construct(
        private ?BotMap $map = null,
        private ?string $secret = null,
    ) {
    }

    public function __invoke(Request $request, string $bot, string $method): JsonResponse
    {
        $map = $this->map ?? $this->resolveMap();
        $secret = $this->secret ?? $this->resolveSecret();

        if ($secret === null || $secret === '') {
            return new JsonResponse(['error' => 'BotMap HTTP auth secret is not configured'], 500);
        }

        $body = (string) $request->getContent();
        $signature = (string) $request->header(self::AUTH_HEADER);

        $expected = hash_hmac('sha256', $bot . "\n" . $method . "\n" . $body, $secret);
        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return new JsonResponse(['error' => 'Invalid BotMap signature'], 401);
        }

        $params = (array) $request->json()->all();

        try {
            $result = $map->for($bot)->call($method, $params);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['error' => "Unknown bot [{$bot}]"], 404);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 502);
        }

        return new JsonResponse($result);
    }

    private function resolveMap(): BotMap
    {
        if (function_exists('app')) {
            $container = app();
            if ($container->bound(BotMap::class)) {
                return $container->make(BotMap::class);
            }
        }

        throw new RuntimeException(
            'BotMap is not resolvable for the HTTP endpoint — bind ' . BotMap::class . ' in the container.',
        );
    }

    private function resolveSecret(): ?string
    {
        if (function_exists('config')) {
            try {
                $configured = config('teleframe.botmap.secret');
                if (is_string($configured) && $configured !== '') {
                    return $configured;
                }
            } catch (Throwable) {
                // fall through to env
            }
        }

        $env = getenv('TELEFRAME_BOTMAP_SECRET');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        return null;
    }
}