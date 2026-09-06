<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Providers;

use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;

class TeleframeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/teleframe.php', 'teleframe');

        $this->app->singleton(TeleframeClient::class, function ($app) {
            $config = $app['config']['teleframe'] ?? $app['config']['telegram'] ?? [];
            return new TeleframeClient(
                defaultApiId: (int)($config['api_id'] ?? 0),
                defaultApiHash: (string)($config['api_hash'] ?? ''),
                defaultBotToken: $config['bot_token'] ?? $config['default_bot_token'] ?? null,
                defaultProxyConfig: $config['proxy'] ?? null,
                defaultUserSession: $config['user_session'] ?? null,
                defaultBotSession: $config['bot_session'] ?? null,
                defaultDcId: (int)($config['dc_id'] ?? 2)
            );
        });

        $this->app->singleton(TeleframeAuthService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/teleframe.php' => config_path('teleframe.php'),
            ], 'teleframe-config');

            $this->commands([
                \MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\PollCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\DoctorCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand::class,
            ]);
        }

        if (isset($this->app['router'])) {
            /** @var Router $router */
            $router = $this->app['router'];
            $router->aliasMiddleware('tg.miniapp', VerifyMiniAppInitData::class);

            // Register Route Macro for simple Webhook endpoint declaration
            $router->macro('telegramWebhook', function (string $uri = 'telegram/webhook') use ($router) {
                return $router->post($uri, \MeRezaRezaei\Teleframe\Laravel\Http\Controllers\TelegramWebhookController::class);
            });
        }
    }
}
