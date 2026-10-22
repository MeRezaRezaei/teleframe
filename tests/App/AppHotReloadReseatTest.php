<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory as ConsoleComponentsFactory;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountLifecycleObserver;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * F10 — hot reload covered ACCOUNTS and not APP definitions.
 *
 * Accounts re-seated every rotation (`reseatAccounts`); app definitions and
 * their scheduled calls did not. `AppServiceProvider::boot()` installs the
 * seeded apps' triggers onto the shared append-only HandlerRegistry ONCE, and
 * the app registry is a lazily resolved singleton — so an app added or changed
 * at runtime never reached a long-lived daemon and an app's config change
 * needed a RESTART. The operator's capability 6 (a bot/user app framework
 * whose apps are registered at runtime) was half a feature next to capability 1.
 *
 * What this pins:
 *  - an app DEFINED AT RUNTIME gets its triggers onto the shared hot table on
 *    the next rotation, with no restart and no pub/sub;
 *  - the daemon's own apps half of the shared handler pipeline fires it;
 *  - a CHANGED definition is reported rather than silently half-applied;
 *  - a REMOVED app is reported, because the shared table cannot un-install;
 *  - a config row the live registry does not hold is NAMED, never swallowed;
 *  - scheduled calls re-arm when the call set CHANGES and never otherwise
 *    (arming dials the schedule host, so it must not run every rotation);
 *  - the `{kind:'apps'}` reload payload is recognised, and it does not re-seat
 *    accounts — nor does an accounts payload re-arm app schedules.
 *
 * Every command here is a pure reseat on a testbench container: no Redis, no
 * credentials, no wire.
 */
final class AppHotReloadReseatTest extends TestCase
{
    /** @var list<string> */
    private static array $fired = [];

    /** @var list<array<string, mixed>> every payload handed to the schedule seam */
    private array $installed = [];

    private BufferedOutput $output;

    protected function setUp(): void
    {
        HandlerRegistry::reset();
        self::$fired = [];

        parent::setUp();

        $this->output = new BufferedOutput;

        $this->app->instance(AppSupport::SCHEDULES_KEY, new ReseatRecordingSchedulePublisher($this->installed));
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [AppServiceProvider::class];
    }

    // ── the defect: a runtime-defined app is not reachable ──────────────

    public function test_an_app_defined_at_runtime_is_installed_on_the_shared_handler_table_by_the_reseat(): void
    {
        $command = $this->command();

        // What the daemon looked like before the app existed: boot installed
        // nothing, the rotation must be the thing that installs it.
        self::assertSame([], $this->tagsFor('runtime-reader'));

        $this->defineAtRuntime('runtime-reader', 'updateNewMessage');

        self::assertSame([], $this->tagsFor('runtime-reader'), 'defining it alone installs nothing yet');

        $this->reseat($command);

        self::assertSame(['app:runtime-reader'], $this->tagsFor('runtime-reader'),
            'the reseat installed the runtime-defined app onto the SHARED hot table — no restart, no pub/sub');
    }

    public function test_the_reseated_runtime_app_actually_fires_in_the_daemon_process(): void
    {
        $this->defineAtRuntime('runtime-reader', 'updateNewMessage');

        $this->reseat($this->command());

        // Dispatch through the same shared table the daemon's ingest loop reads.
        $this->dispatchUpdateNewMessage();

        self::assertSame([7], self::$fired, 'the app the reseat installed is reachable, not merely present');
    }

    public function test_an_unchanged_app_is_not_reinstalled_or_reported_on_every_rotation(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('steady', 'updateNewMessage');

        $this->reseat($command);
        $this->lines();

        $this->reseat($command);
        $printed = $this->lines();

        self::assertCount(1, $this->tagsFor('steady'),
            'install() is idempotent per app name — a stable definition must not stack duplicate rows');
        self::assertStringNotContainsString('steady', $this->output->fetch(),
            'an unchanged app is not news; only a genuine change is announced');
    }

    public function test_a_changed_definition_is_announced_rather_than_silently_half_applied(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('mover', 'updateNewMessage');
        $this->reseat($command);
        $this->lines();

        // Same name, a DIFFERENT trigger: a genuine config change.
        $this->defineAtRuntime('mover', 'updateEditMessage');
        $this->reseat($command);

        $printed = $this->lines();
        self::assertStringContainsString('mover', $printed,
            'the changed app is named so the operator is not left believing a restart was needed for nothing');
        self::assertStringContainsString('Re-seated app', $printed);
    }

    public function test_a_removed_app_is_reported_because_the_shared_table_cannot_uninstall_it(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('leaver', 'updateNewMessage');
        $this->reseat($command);
        $this->lines();

        $this->replaceRegistryWith([]); // the app left the registry
        $this->reseat($command);

        $printed = $this->lines();
        self::assertStringContainsString('leaver', $printed,
            'a removal the daemon CANNOT apply must be said out loud, not swallowed');
        self::assertStringContainsString('restart', $printed,
            'the message names the one thing that actually un-installs it');
        self::assertSame(['app:leaver'], $this->tagsFor('leaver'),
            'stated limit: the shared handler table is append-only, so the row is still live');
    }

    public function test_a_config_row_the_live_registry_does_not_hold_is_named_not_swallowed(): void
    {
        // Config gains a row AFTER the registry singleton was resolved: the row
        // is in config and NOT in the registry, so nothing else would ever say so.
        $this->app->config->set('teleframe.app.apps', [['name' => 'ghost', 'mode' => 'user']]);
        $this->replaceRegistryWith([]);

        $command = $this->command();
        $this->reseat($command);

        $printed = $this->lines();
        self::assertStringContainsString('ghost', $printed);
        self::assertStringContainsString('NOT installed', $printed,
            'a config-declared app that never reached the registry is the silent no-op this names');
    }

    public function test_the_rotation_itself_calls_the_app_reseat_not_only_a_direct_call(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('rotated-in', 'updateNewMessage');

        // The REAL rotation path: buildDaemon()'s onLoop closure, fired once.
        // Calling reseatApps() directly would pass even if the rotation had
        // stopped calling it — which is precisely how "the reseat exists" and
        // "the reseat runs" drift apart.
        $this->rotate($command);

        self::assertSame(['app:rotated-in'], $this->tagsFor('rotated-in'),
            'one rotation of the real daemon installs the runtime-defined app — the reseat is ON the rotation');
    }

    // ── the scheduled-call half ─────────────────────────────────────────

    public function test_the_first_rotation_never_arms_schedules(): void
    {
        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 60, 'messages.sendMessage')]);

        $this->reseat($this->command());

        self::assertSame([], $this->installed,
            'arming app schedules is the host\'s `teleframe:app calls --install` gesture, not this daemon\'s boot');
    }

    public function test_a_changed_scheduled_call_is_rearmed_without_a_restart(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 60, 'messages.sendMessage')]);
        $this->reseat($command); // first pass: records only

        self::assertSame([], $this->installed);

        // The interval changed — the event that needs the schedule host.
        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 30, 'messages.sendMessage')]);
        $this->reseat($command);

        self::assertCount(1, $this->installed, 'the changed call set re-armed once');
        self::assertSame(30, $this->installed[0]['app-call-caller-ping-'.substr(
            hash('sha256', "caller\0ping"),
            0,
            12,
        )]['interval_seconds'] ?? null, 'the NEW cadence is what got armed — the schedule id is a pure '
            .'function of the (app, call) pair, so it refreshed in place instead of stacking a second schedule');
    }

    public function test_an_unchanged_scheduled_call_never_re_dials_the_schedule_host(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 60, 'messages.sendMessage')]);

        $this->reseat($command);
        $this->reseat($command);
        $this->reseat($command);

        self::assertSame([], $this->installed, 'arming dials Temporal — it must not happen per rotation');
    }

    public function test_a_skipped_schedule_is_reported_rather_than_claimed_as_armed(): void
    {
        $command = $this->command();
        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 60, 'messages.sendMessage')]);
        $this->reseat($command);

        $this->defineAtRuntime('caller', 'updateNewMessage', [AppCall::every('ping', 90, 'messages.sendMessage')]);
        $this->reseat($command);

        $printed = $this->lines();
        self::assertStringContainsString('Re-armed', $printed);
        self::assertStringContainsString('NOT armed', $printed,
            'the null driver skips every schedule — saying "re-armed" over that is the visible no-op this removes');
    }

    // ── the reload signal ───────────────────────────────────────────────

    public function test_the_apps_reload_payload_is_recognised_and_announced(): void
    {
        $command = $this->command();
        $this->lines();

        $this->note($command, json_encode([
            'kind' => DaemonCommand::RELOAD_KIND_APPS,
            'app' => 'assistant',
            'change' => 'changed',
        ], JSON_THROW_ON_ERROR));

        $this->reseat($command);

        self::assertStringContainsString('app definitions', $this->lines(),
            'a {kind:apps} payload must reach the app reseat — it was dropped on the floor before this');
    }

    public function test_an_apps_payload_does_not_re_seat_accounts(): void
    {
        $command = $this->command();
        $this->lines();

        $this->note($command, json_encode(['kind' => DaemonCommand::RELOAD_KIND_APPS], JSON_THROW_ON_ERROR));

        self::assertFalse($this->reloadRequested($command),
            'the two registries are independent: an app write must not re-read tf_accounts');
    }

    public function test_an_accounts_payload_does_not_re_arm_app_schedules(): void
    {
        $command = $this->command();

        $this->note($command, json_encode([
            'kind' => AccountLifecycleObserver::RELOAD_KIND,
            'account_id' => 1,
            'change' => 'added',
        ], JSON_THROW_ON_ERROR));

        self::assertTrue($this->reloadRequested($command), 'the accounts half still works, unchanged');
        self::assertFalse($this->appsReloadRequested($command),
            'an account write must not reach the app reseat — each kind acts on its own registry');
    }

    public function test_an_unknown_or_malformed_payload_acts_on_neither(): void
    {
        $command = $this->command();

        foreach (['{"kind":"something-else"}', '{"app":"assistant"}', 'not json at all', '[]', 'null'] as $payload) {
            $this->note($command, $payload);
        }

        self::assertFalse($this->reloadRequested($command), 'unrecognised payloads are somebody else\'s channel traffic');
        self::assertFalse(
            $this->appsReloadRequested($command),
            'an unrecognised payload must not reach the APP reseat either — each kind acts on its own registry',
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function command(): DaemonCommand
    {
        $command = $this->app->make(DaemonCommand::class);
        $command->setLaravel($this->app);

        $components = new ReflectionProperty(DaemonCommand::class, 'components');
        $components->setAccessible(true);
        $components->setValue($command, new ConsoleComponentsFactory($this->output));

        $output = new ReflectionProperty(Command::class, 'output');
        $output->setAccessible(true);
        $output->setValue($command, $this->output);

        return $command;
    }

    /**
     * Fire ONE rotation of the daemon exactly as `Daemon::run()` does: build
     * the supervisor through the command's own buildDaemon() and invoke the
     * onLoop closure it registered. Nothing dials — the account reseat finds no
     * registry and the bus drain fails into its own warning.
     */
    private function rotate(DaemonCommand $command): void
    {
        $build = new ReflectionMethod(DaemonCommand::class, 'buildDaemon');
        $build->setAccessible(true);

        $daemon = $build->invoke($command, []);

        $onLoop = new ReflectionProperty(Daemon::class, 'onLoop');
        $onLoop->setAccessible(true);

        $loop = $onLoop->getValue($daemon);
        self::assertNotNull($loop, 'buildDaemon() registered an onLoop callback');

        $loop();
    }

    /** Read and clear what the command has printed since the last read. */
    private function lines(): string
    {
        return $this->output->fetch();
    }

    /** One rotation's app re-seat — exactly what the onLoop callback calls. */
    private function reseat(DaemonCommand $command): void
    {
        $method = new ReflectionMethod(DaemonCommand::class, 'reseatApps');
        $method->setAccessible(true);
        $method->invoke($command);
    }

    private function note(DaemonCommand $command, string $payload): void
    {
        $method = new ReflectionMethod(DaemonCommand::class, 'noteReloadPayload');
        $method->setAccessible(true);
        $method->invoke($command, $payload);
    }

    private function reloadRequested(DaemonCommand $command): bool
    {
        return $this->flag($command, 'reloadRequested');
    }

    private function appsReloadRequested(DaemonCommand $command): bool
    {
        return $this->flag($command, 'appsReloadRequested');
    }

    private function flag(DaemonCommand $command, string $name): bool
    {
        $property = new ReflectionProperty(DaemonCommand::class, $name);
        $property->setAccessible(true);

        return (bool) $property->getValue($command);
    }

    /**
     * Register an app in the LIVE registry after boot — the operator's
     * "apps are registered at runtime" gesture. `register()` would install its
     * triggers itself, so the registry seam is used directly here: a host can
     * bind rows onto `teleclient.app.registry` and the daemon must cover them.
     *
     * @param  list<AppCall>  $calls
     */
    private function defineAtRuntime(string $name, string $match, array $calls = []): void
    {
        /** @var AppRegistry $registry */
        $registry = $this->app->make(AppSupport::REGISTRY_KEY);

        if ($registry->has($name)) {
            $this->replaceRegistryWith(array_values(array_filter(
                $registry->all(),
                static fn (AppDefinition $app): bool => $app->name !== $name,
            )));
        }

        $this->app->make(AppSupport::REGISTRY_KEY)->define(AppDefinition::define(
            $name,
            AppMode::User,
            [AppTrigger::on($match, static function (Update $u): void {
                self::$fired[] = $u->accountId;
            })],
            $calls,
        ));
    }

    /**
     * Swap the live registry wholesale — how an app leaves the runtime set.
     *
     * @param  list<AppDefinition>  $apps
     */
    private function replaceRegistryWith(array $apps): void
    {
        $registry = new AppRegistry;
        foreach ($apps as $app) {
            $registry->define($app);
        }

        $this->app->instance(AppSupport::REGISTRY_KEY, $registry);
        $this->app->instance(AppSupport::SERVICE_KEY, new AppRuntime(
            $registry,
            $this->app->make(AppSupport::SCHEDULES_KEY),
            $this->app->make(AppSupport::TRANSPORT_KEY),
        ));
    }

    /**
     * @return list<string> the tags the shared hot table carries for one app
     */
    private function tagsFor(string $app): array
    {
        $tags = [];

        foreach (HandlerRegistry::instance()->all() as $handler) {
            if ($handler->tag === 'app:'.$app) {
                $tags[] = $handler->tag;
            }
        }

        return $tags;
    }

    private function dispatchUpdateNewMessage(): void
    {
        $matcher = new HandlerMatcher(HandlerRegistry::instance());
        $handler = $matcher->match('updateNewMessage');

        self::assertNotNull($handler, 'the trigger is on the shared hot table');

        $call = $handler->handler->handler;
        self::assertIsCallable($call, 'the registered handler is invocable — that is what the reseat installed');
        $call(Update::fromBus(['_' => 'updateNewMessage'], 7));
    }
}

/**
 * The schedule seam, recorded. Every install() is one payload; nothing dials.
 */
final class ReseatRecordingSchedulePublisher implements SchedulePublisher
{
    /** @param list<array<string, mixed>> $installed */
    public function __construct(
        private array &$installed,
    ) {}

    public function install(array $schedules): array
    {
        $this->installed[] = $schedules;

        $status = [];
        foreach ($schedules as $id => $spec) {
            $status[$id] = 'skipped (null schedule driver)';
        }

        return $status;
    }

    public function describe(): string
    {
        return 'recording double — every install() is captured, nothing dials.';
    }
}
