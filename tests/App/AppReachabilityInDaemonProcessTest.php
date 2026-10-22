<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * BREAK 1 — the app triggers must be installed in EVERY process that
 * consumes updates, not only in the short-lived `teleframe:app register`
 * artisan run whose static HandlerRegistry dies on exit.
 *
 * Each test builds the process the way `teleframe:daemon` builds it: a real
 * app container booted from config, with `teleframe.app.apps` already
 * populated (what a published config file gives a host). Nothing here calls
 * AppResponder::install() by hand — that is the whole point. A declarative
 * app defined the way the docs say must fire inside a daemon-shaped process.
 */
final class AppReachabilityInDaemonProcessTest extends TestCase
{
    /**
     * Set in getEnvironmentSetUp() so the config exists BEFORE the providers
     * boot — the same ordering a host's config/teleframe/app.php has. The
     * handler closure cannot capture $this, so it records into a static.
     *
     * @var list<int>
     */
    public static array $fired = [];

    /**
     * The app seeded into every boot of this class — the exact shape a
     * published config/teleframe/app.php carries. A config file is executed
     * PHP, so a closure is a legal trigger row (AppServiceProvider's row
     * mapper accepts one).
     *
     * @return list<array<string, mixed>>
     */
    private static function apps(): array
    {
        return [
            [
                'name' => 'daemon-reader',
                'mode' => 'user',
                'triggers' => [
                    [
                        'match' => 'updateNewMessage',
                        'handler' => static function (Update $u): void {
                            self::$fired[] = $u->accountId;
                        },
                    ],
                ],
                'calls' => [],
            ],
        ];
    }

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('teleframe.app.apps', self::apps());
    }

    /**
     * A daemon-shaped process starts with an EMPTY handler table, then the
     * providers boot into it. Resetting before the container is created is
     * the process boundary — after it, the table belongs to the boot.
     */
    protected function setUp(): void
    {
        HandlerRegistry::reset();
        self::$fired = [];

        parent::setUp();
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    /**
     * The headline: an app seeded through config (the documented way) has
     * its trigger live on the shared hot table in a daemon-shaped process,
     * and that trigger FIRES on a real dispatch — with no manual install.
     */
    public function test_a_config_seeded_app_trigger_fires_in_a_daemon_shaped_process(): void
    {
        $this->assertTrue(
            $this->app->make('teleclient.app.registry')->has('daemon-reader'),
            'the app is defined from config at boot',
        );

        $this->app->make(UpdateDispatcher::class)->dispatch(
            Update::fromMirror(ReachabilityMessage::reachabilityMessage(42, 7, 'ping'), 42),
        );

        self::assertSame([42], self::$fired, 'the declarative app trigger fired inside the daemon-shaped process');
    }

    /**
     * The break was that install() lived ONLY in AppCommand. Prove the
     * artisan one-shot is not what makes it work: a process that never runs
     * the command already has the trigger.
     */
    public function test_the_trigger_is_live_before_any_artisan_app_command_runs(): void
    {
        $matcher = new HandlerMatcher($this->app->make(HandlerRegistry::class));

        self::assertNotNull(
            $matcher->match('updateNewMessage'),
            'the app trigger is on the shared hot table at boot — no install() call needed',
        );
    }

    /**
     * A re-install in the SAME process must not double-register: the
     * provider boot install and the artisan `register` action share one
     * process, and the shared table is append-only. A duplicated trigger
     * would run the same closure once per copy.
     */
    public function test_reinstalling_in_one_process_does_not_double_register(): void
    {
        $table = $this->app->make(HandlerRegistry::class);
        self::assertSame(1, $table->count());

        // The artisan register action re-installs the same app in the same
        // process — it must be a no-op, not a duplicate.
        $this->app->make('teleclient.app.service')->responder()->install(
            $this->app->make('teleclient.app.registry')->resolve('daemon-reader'),
        );

        self::assertSame(1, $table->count(), 'install() is idempotent per app name');

        $this->app->make(UpdateDispatcher::class)->dispatch(
            Update::fromMirror(ReachabilityMessage::reachabilityMessage(42, 8, 'ping'), 42),
        );

        self::assertSame([42], self::$fired, 'the trigger fired exactly once');
    }
}
