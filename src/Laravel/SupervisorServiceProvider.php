<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Laravel\Console\SuperviseCommand;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpawnerContract;
use MeRezaRezaei\Teleframe\Supervisor\ProcessState;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use Throwable;

/**
 * SupervisorServiceProvider — the wiring that makes "the host writes nothing"
 * true: `php artisan teleframe:supervise` and `GET <prefix>/ops` both work
 * straight after `composer require`, with no host file to paste and no
 * polling job to write (the delegation docs/scaling.md used to hand over).
 *
 * Three seams, all optional, none resolving a process at boot:
 *  - `teleclient.supervisor.spawner` → ProcessSpawnerContract (default
 *    SystemProcessSpawner; binding it opens no process — proc_open lives in
 *    spawn(), which only the supervision loop calls);
 *  - `teleclient.supervisor.redis` → RedisConnectionContract for the ops
 *    read-model (default: the LaravelRedisAdapter over the bus connection;
 *    falls back to null when Redis is not configured, and the ops snapshot
 *    then reports `bus: false` instead of failing);
 *  - `teleclient.supervisor.state` → callable returning the live supervisor
 *    rows. UNBOUND by default: a supervisor's report lives in the process
 *    that runs it, and a web request cannot see another process's memory —
 *    binding a shared reader is the host's choice, and the snapshot is honest
 *    about the absence.
 */
final class SupervisorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // config('teleframe') (including the new `supervisor` block) is
        // merged by TeleframeServiceProvider from the same file — no second
        // merge here, so a published host config is never re-defaulted.
        $this->registerSpawner();
        $this->registerRedis();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SuperviseCommand::class,
            ]);
        }
    }

    /**
     * teleclient.supervisor.spawner — the process seam. Bound as a
     * singleton, but the default is inert: nothing is spawned until the
     * supervision loop asks for it.
     */
    private function registerSpawner(): void
    {
        $this->app->singleton('teleclient.supervisor.spawner', static fn (): ProcessSpawnerContract => new SystemProcessSpawner);
    }

    /**
     * teleclient.supervisor.redis — the bus state the ops read-model reads
     * (STRIKES_KEY, the dead-letter stream, the stream backlog). Deferred
     * and fully guarded: no redis config, no redis extension, or a failed
     * connection binds null and the ops surface degrades honestly.
     */
    private function registerRedis(): void
    {
        $this->app->singleton(OpsReadModel::REDIS_KEY, function ($app): ?RedisConnectionContract {
            // EVERY read path is inside the guard: a host whose redis is
            // misconfigured (or whose bus binding throws) must see a degraded
            // ops snapshot, not a 500 on the dashboard.
            try {
                if ($app->bound(RedisConnectionContract::class)) {
                    $bound = $app->make(RedisConnectionContract::class);
                    if ($bound instanceof RedisConnectionContract) {
                        return $bound;
                    }
                }

                /** @var Repository $config */
                $config = $app->make('config');
                $connection = (string) ($config->get('teleframe.bus.connection') ?? 'default');

                if (! class_exists(LaravelRedisAdapter::class) || ! $app->bound('redis')) {
                    return null;
                }

                return new LaravelRedisAdapter($app->make('redis')->connection($connection));
            } catch (Throwable) {
                return null;
            }
        });
    }

    /**
     * teleclient.supervisor.state — NOT bound by default (see the class
     * docblock): a supervisor's rows are in the supervising process. Hosts
     * that publish them bind a callable returning
     * `array_map(fn (ProcessState $row) => $row->toArray(), $report)`.
     *
     * @return list<array<string, mixed>>
     */
    public static function stateRows(ProcessState ...$rows): array
    {
        return array_map(static fn (ProcessState $row): array => $row->toArray(), $rows);
    }
}
