<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Redis\RedisManager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Subscriptions\UpdateStoredHandler;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Teleframe;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

class TeleframeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/teleframe.php', 'teleframe');

        $this->app->singleton(AccountContext::class, static fn (): AccountContext => new AccountContext());
        $this->app->singleton(SchemaRegenerator::class);
        $this->app->singleton(UpdateIngestor::class, static fn ($app): UpdateIngestor => new UpdateIngestor(
            events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
        ));
        $this->app->singleton(EntityAggregator::class);
        $this->app->singleton(Teleclient::class);

        $this->app->singleton(HandlerRegistry::class);

        // PSR-3 logging seam (gap #1 closed 2026-09-09): a host-registered
        // Psr\Log\LoggerInterface binding wins; otherwise honor the
        // teleframe.logging.logger FQCN from config; otherwise stay silent
        // with a NullLogger. The engine never requires a concrete logger.
        $this->app->singleton(LoggerInterface::class, static function ($app): LoggerInterface {
            /** @var ConfigRepository $config */
            $config = $app->make('config');
            $class = (string) $config->get('teleframe.logging.logger', '');

            if ($class !== '' && class_exists($class)) {
                $logger = new $class();

                if ($logger instanceof LoggerInterface) {
                    return $logger;
                }
            }

            return new NullLogger();
        });

        $this->app->singleton(UpdateDispatcher::class, static function ($app): UpdateDispatcher {
            $sends = $app->bound(CacheInterface::class)
                ? $app->make(CacheInterface::class)
                : new InMemoryCache();

            return new UpdateDispatcher(
                $app->make(HandlerRegistry::class),
                new Pipeline(),
                $app,
                $sends,
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->singleton(Teleframe::class, static fn ($app): Teleframe => new Teleframe($app));

        $this->app->singleton(\MeRezaRezaei\Teleframe\Vault\Vault::class);

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
                defaultDcId: (int)($config['dc_id'] ?? 2),
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->bind(BackfillCommand::SCOPE_RESOLVER_KEY, static function ($app): callable {
            return static function (int $accountId) use ($app): UserAccountScope {
                /** @var ConfigRepository $config */
                $config = $app->make('config');

                foreach ((array) $config->get('teleframe.daemon.accounts', []) as $account) {
                    if ((int) ($account['account_id'] ?? 0) === $accountId) {
                        return AccountWorker::buildLiveScope($account);
                    }
                }

                throw new RuntimeException(
                    "no teleframe.daemon.accounts entry with account_id={$accountId} "
                    . '(the backfill command resolves its session through that registry)',
                );
            };
        });

        // Default batch writer: each fetched page lands as a plain
        // messages.messages ingest under the account's tenancy (route
        // dedup is deliberately NOT used — see BackfillCommand).
        $this->app->bind(BackfillCommand::INGESTER_KEY, static function ($app): callable {
            return static function (int $accountId) use ($app): callable {
                $ingestor = $app->make(UpdateIngestor::class);

                return static function (array $messages) use ($ingestor, $accountId): array {
                    try {
                        $root = $ingestor->ingest([
                            '_' => 'messages.messages',
                            'messages' => $messages,
                            'chats' => [],
                            'users' => [],
                        ], $accountId);

                        return ['stored' => count($messages), 'root' => $root->getKey()];
                    } catch (Throwable) {
                        return ['stored' => 0]; // v1 report-only: a failing batch never kills the fetch loop
                    }
                };
            };
        });

        // Backup vault factory (Phase 2, Task 6): callable(string $setId):
        // VaultInterface for teleframe:backup. Driver-aware — 'memory'
        // shares one InMemoryVault per set for the process (so run→restore
        // round-trips offline; nothing is persisted), while 'telegram'
        // reuses the backfill SCOPE_RESOLVER_KEY seam (shared
        // daemon.accounts registry, AccountWorker::buildLiveScope) to build
        // the real channel-backed TelegramVault.
        $this->app->bind(BackupCommand::VAULT_FACTORY_KEY, static function ($app): callable {
            return static function (string $setId) use ($app): VaultInterface {
                /** @var ConfigRepository $config */
                $config = $app->make('config');
                $driver = (string) $config->get('teleframe.backup.driver', 'memory');

                if ($driver === 'memory') {
                    $key = 'teleframe.backup.vault.' . $setId;
                    if (! $app->bound($key)) {
                        $app->bind($key, static fn (): VaultInterface => new InMemoryVault(), true);
                    }

                    /** @var VaultInterface */
                    return $app->make($key);
                }

                if ($driver !== 'telegram') {
                    throw new RuntimeException("unknown backup driver \"{$driver}\" — expected memory|telegram.");
                }

                $accountId = (int) ($config->get('teleframe.backup.account') ?? 0);
                if ($accountId <= 0) {
                    throw new RuntimeException(
                        'backup driver "telegram" needs teleframe.backup.account (a daemon.accounts account_id).',
                    );
                }

                $resolver = $app->make(BackfillCommand::SCOPE_RESOLVER_KEY);
                if (! is_callable($resolver) || is_string($resolver)) {
                    throw new RuntimeException(
                        BackfillCommand::SCOPE_RESOLVER_KEY . ' must bind a callable(int): UserAccountScope',
                    );
                }

                return TelegramVault::forScope($resolver($accountId), $setId);
            };
        });

        $this->app->singleton(TeleframeAuthService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/teleframe.php' => config_path('teleframe.php'),
            ], 'teleframe-config');

            $this->publishes([
                __DIR__ . '/../Stubs/miniapp' => base_path(),
            ], 'teleframe-miniapp');

            $this->commands([
                \MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\PollCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\DoctorCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAppCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAccountCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\VaultListCommand::class,
                \MeRezaRezaei\Teleframe\Laravel\Console\VaultUseDefaultCommand::class,
                RegenerateCommand::class,
                IngestCommand::class,
                BackfillCommand::class,
                BackupCommand::class,
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

        // The event intake row funnels into the handler pipeline (friction
        // I.1): stored updates reach the registered onMessage surface with
        // the same registry + onion the bus path uses.
        if (isset($this->app['events']) && class_exists(UpdateStored::class)) {
            /** @var EventDispatcher $events */
            $events = $this->app['events'];
            $events->listen(UpdateStored::class, UpdateStoredHandler::class);
        }
    }
}
