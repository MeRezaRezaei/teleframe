<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Redis\RedisManager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Teleclient;
use RuntimeException;

class TeleframeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/teleframe.php', 'teleframe');

        $this->app->singleton(SchemaRegenerator::class);
        $this->app->singleton(UpdateIngestor::class, static fn ($app): UpdateIngestor => new UpdateIngestor(
            events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
        ));
        $this->app->singleton(EntityAggregator::class);
        $this->app->singleton(Teleclient::class);

        $this->app->bind(RedisConnectionContract::class, static function ($app): RedisConnectionContract {
            $manager = $app->bound('redis') ? $app->make('redis') : null;

            if ($manager instanceof RedisManager) {
                /** @var ConfigRepository $config */
                $config = $app->make('config');
                $connection = (string) $config->get('teleframe.bus.connection', 'default');

                return new LaravelRedisAdapter($manager->connection($connection));
            }

            // No illuminate redis service: fail loudly. Tests bind the
            // in-memory ArrayRedis double to this contract themselves; a
            // silent fallback here would hide a misconfigured host app.
            throw new RuntimeException(
                'teleframe bus requires the illuminate redis service (app("redis")); '
                . 'install illuminate/redis (predis or phpredis driver) or bind '
                . RedisConnectionContract::class . ' yourself.',
            );
        });

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
                RegenerateCommand::class,
                IngestCommand::class,
            ]);

            $this->loadMigrationsFrom(dirname(__DIR__, 3) . '/migrations');
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
