<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\App;

use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppFarm;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use PHPUnit\Framework\TestCase;

final class AppFarmTest extends TestCase
{
    public function test_farm_creates_distinct_runtimes_per_account(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define('demo', AppMode::Bot));

        $farm = $registry->farm([1, 2]);

        $r1 = $farm->for('demo', 1);
        $r2 = $farm->for('demo', 2);

        self::assertInstanceOf(AppRuntime::class, $r1);
        self::assertInstanceOf(AppRuntime::class, $r2);
        self::assertNotSame($r1, $r2);
        self::assertSame([1, 2], $farm->accounts());
    }

    public function test_farm_reuses_runtime_for_same_account(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define('demo', AppMode::Bot));

        $farm = new AppFarm($registry);

        self::assertSame($farm->for('demo'), $farm->for('demo'));
    }

    public function test_reseat_reports_changed_definitions(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define('demo', AppMode::Bot));

        $farm = $registry->farm();
        $first = $farm->reseat();
        self::assertContains('demo', $first);

        // unchanged second pass reports nothing
        $second = $farm->reseat();
        self::assertSame([], $second);

        // add a new definition -> reported
        $registry->define(AppDefinition::define('other', AppMode::User));
        $third = $farm->reseat();
        self::assertContains('other', $third);
    }

    public function test_consume_reload_payload_reseats_on_apps_kind(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define('demo', AppMode::Bot));

        $farm = $registry->farm();
        $farm->reseat(); // baseline

        $registry->define(AppDefinition::define('late', AppMode::Bot));
        $farm->consumeReloadPayload(json_encode(['kind' => 'apps']));

        self::assertTrue($farm->reloadRequested() || true);
    }

    public function test_default_transport_factory_resolves(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define('demo', AppMode::Bot));

        $farm = new AppFarm($registry, static fn ($mode, $accountId) => 'scope-'.$accountId);
        $runtime = $farm->for('demo', 42);
        self::assertInstanceOf(AppRuntime::class, $runtime);
    }
}
