<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * The operations surface: `GET <prefix>/ops` returns the read-model over the
 * state the package already records, under the same host guard as the rest of
 * the dashboard group.
 */
final class OpsRouteTest extends TestCase
{
    public function test_the_ops_route_is_registered_under_the_dashboard_group(): void
    {
        DashboardRoutes::register();

        self::assertTrue(Route::has('teleframe.ops.index'));

        $route = Route::getRoutes()->getByName('teleframe.ops.index');
        self::assertSame('teleframe/ops', $route->uri());
        self::assertSame(['web', 'auth', 'tg.readonly'], $route->gatherMiddleware());
    }

    public function test_the_ops_endpoint_reports_the_dead_letter_ledger(): void
    {
        $redis = new ArrayRedis;
        $redis->hset(StreamSchema::STRIKES_KEY, '7-0', '2');
        $redis->xadd(StreamSchema::DL, '*', [
            'account_id' => '5',
            'update' => '{}',
            'ts' => '1720000000',
            'reason' => 'ingest-failed',
            'error' => 'boom',
        ]);

        $this->app->instance(OpsReadModel::REDIS_KEY, $redis);

        DashboardRoutes::register(middleware: []);

        $this->get('teleframe/ops')
            ->assertOk()
            ->assertJsonPath('bus', true)
            ->assertJsonPath('summary.dead_letter_total', 1)
            ->assertJsonPath('dead_letters.0.reason', 'ingest-failed')
            ->assertJsonPath('failing_ingest.0.entry_id', '7-0');
    }

    public function test_the_ops_endpoint_is_honest_when_no_redis_seam_is_bound(): void
    {
        DashboardRoutes::register(middleware: []);

        $this->get('teleframe/ops')
            ->assertOk()
            ->assertJsonPath('bus', false)
            ->assertJsonPath('daemon_rotation', 'not-recorded');
    }

    public function test_a_published_supervisor_state_seam_appears_in_the_snapshot(): void
    {
        $this->app->instance(OpsReadModel::SUPERVISOR_STATE_KEY, static fn (): array => [
            ['name' => 'ingest', 'status' => 'running', 'pid' => 4242, 'restarts' => 1, 'last_exit_code' => null, 'backoff_seconds' => 0, 'next_attempt_at' => 0],
        ]);

        DashboardRoutes::register(middleware: []);

        $this->get('teleframe/ops')
            ->assertOk()
            ->assertJsonPath('supervised.0.name', 'ingest')
            ->assertJsonPath('summary.supervised_running', 1);
    }
}
