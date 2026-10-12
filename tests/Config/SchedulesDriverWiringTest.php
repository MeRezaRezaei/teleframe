<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use Illuminate\Contracts\Container\Container;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * The five `schedules` keys the audit proved DEAD, because two providers
 * hardcoded the null publisher in a ZERO-ARG closure.
 *
 *   teleframe.tstore.schedules            TeleframeServiceProvider — was :667
 *   teleframe.tstore.schedules_dsn        ditto
 *   teleframe.tstore.schedules_namespace  ditto
 *   teleframe.notify.schedules            NotifyServiceProvider — was :126
 *   teleframe.notify.schedules_dsn        ditto
 *
 * The tell was not that the keys were unread — it was that the module TOLD the
 * operator to set them: NullSchedulePublisher's refusal message names
 * teleframe.tstore.schedules=temporal. A promise that reaches the terminal and
 * cannot work is the dead-config class at its worst.
 *
 * These are the same two rows Cycle 19 had already fixed for
 * teleframe.app.schedules and teleframe.store.backup.schedule.publisher, so
 * the posture is copied from the worked example rather than invented:
 *
 *   'null'      the shipped default — UNCHANGED, a package that arms recurring
 *               jobs the moment it is installed is a surprise, and that ruling
 *               is in the ledger.
 *   'temporal'  a host-bound client WINS verbatim; else TemporalClientResolver
 *               supplies the package's own SDK-backed client; with neither it
 *               REFUSES AT RESOLVE TIME, by name. Never a silent degrade to
 *               NullSchedulePublisher, which reports 'skipped' while the host
 *               believes its jobs recurred.
 *   else        LOUD.
 *
 * The resolver is bound explicitly in every temporal test. Whether temporal/sdk
 * and ext-grpc are installed is a property of the ENVIRONMENT (CI has no
 * ext-grpc), and a test that lets the environment pick its branch passes and
 * fails for reasons that have nothing to do with the code — pipeline #203
 * failed exactly that way.
 */
final class SchedulesDriverWiringTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    // ---------------------------------------------------------------- tstore

    public function test_the_tstore_seam_still_defaults_to_the_null_publisher(): void
    {
        // The default must not change for an existing host. If this fails, the
        // wiring has started arming schedules on install.
        self::assertInstanceOf(
            NullSchedulePublisher::class,
            $this->tstoreSchedules(),
        );
    }

    public function test_the_tstore_driver_key_reaches_the_seam(): void
    {
        // THE KEY THAT WAS DEAD. Pre-fix, TELEFRAME_TSTORE_SCHEDULES=temporal
        // resolved to a NullSchedulePublisher no matter what.
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.tstore.schedules', 'temporal');
        $this->forgetTstoreSchedules();

        $publisher = $this->tstoreSchedules();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertNotInstanceOf(NullSchedulePublisher::class, $publisher);
        self::assertStringNotContainsString(
            'skipped',
            $publisher->describe(),
            'the temporal driver must never report the success a null publisher reports',
        );
    }

    public function test_the_tstore_dsn_key_reaches_the_publisher(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.tstore.schedules', 'temporal');
        $this->app['config']->set('teleframe.tstore.schedules_dsn', '10.0.0.5:7233');
        $this->forgetTstoreSchedules();

        $publisher = $this->tstoreSchedules();
        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertSame(
            '10.0.0.5:7233',
            $publisher->dsn(),
            'the configured DSN must be the one the publisher uses. Pre-fix there was'
            ."\nno reader for this key at all, so the publisher only ever saw the default.",
        );
    }

    public function test_the_tstore_namespace_key_reaches_the_resolver(): void
    {
        // The namespace is the resolver's OWN second argument — it becomes the
        // Temporal accounting namespace the SDK client is built with. This is
        // what makes the key live; passing the DSN alone is what left it dead.
        $seen = [];
        $this->app->instance(
            TemporalClientResolver::class,
            new TemporalClientResolver(null, static function (string $dsn, string $namespace) use (&$seen): RecurringScheduleClient {
                $seen = [$dsn, $namespace];

                return self::staticFakeClient();
            }),
        );
        $this->app['config']->set('teleframe.tstore.schedules', 'temporal');
        $this->app['config']->set('teleframe.tstore.schedules_dsn', 'front.example:7233');
        $this->app['config']->set('teleframe.tstore.schedules_namespace', 'staging');
        $this->forgetTstoreSchedules();

        $this->tstoreSchedules();

        self::assertSame(
            ['front.example:7233', 'staging'],
            $seen,
            'the configured namespace never reached TemporalClientResolver::resolve().',
        );
    }

    public function test_the_tstore_seam_refuses_an_unknown_driver_instead_of_disabling_schedules(): void
    {
        $this->app['config']->set('teleframe.tstore.schedules', 'carrier-pigeon');
        $this->forgetTstoreSchedules();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected null|temporal');

        $this->tstoreSchedules();
    }

    public function test_the_tstore_seam_refuses_by_name_when_the_sdk_is_unavailable(): void
    {
        $this->bindResolverBuilding(null);
        $this->app['config']->set('teleframe.tstore.schedules', 'temporal');
        $this->forgetTstoreSchedules();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('temporal/sdk');

        $this->tstoreSchedules();
    }

    // ---------------------------------------------------------------- notify

    public function test_the_notify_seam_still_defaults_to_the_null_publisher(): void
    {
        self::assertInstanceOf(
            NullSchedulePublisher::class,
            $this->notifySchedules(),
        );
    }

    public function test_the_notify_driver_key_reaches_the_seam(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.notify.schedules', 'temporal');
        $this->forgetNotifySchedules();

        $publisher = $this->notifySchedules();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertNotInstanceOf(NullSchedulePublisher::class, $publisher);
    }

    public function test_the_notify_dsn_key_reaches_the_publisher(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.notify.schedules', 'temporal');
        $this->app['config']->set('teleframe.notify.schedules_dsn', 'notify.example:7233');
        $this->forgetNotifySchedules();

        $publisher = $this->notifySchedules();
        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertSame(
            'notify.example:7233',
            $publisher->dsn(),
            'the configured DSN must be the one the publisher uses.',
        );
    }

    public function test_the_notify_seam_refuses_an_unknown_driver(): void
    {
        $this->app['config']->set('teleframe.notify.schedules', 'smoke-signal');
        $this->forgetNotifySchedules();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected null|temporal');

        $this->notifySchedules();
    }

    public function test_the_notify_seam_refuses_by_name_when_the_sdk_is_unavailable(): void
    {
        $this->bindResolverBuilding(null);
        $this->app['config']->set('teleframe.notify.schedules', 'temporal');
        $this->forgetNotifySchedules();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('temporal/sdk');

        $this->notifySchedules();
    }

    // --------------------------------------------------------------- honesty

    /**
     * Both seams are singleton bindings, and the audit's defect was a binding
     * that ignored config entirely. A `bind` instead of a `singleton` would
     * make every resolve re-read config, which is a different (and surprising)
     * contract for a stateful publisher.
     */
    public function test_both_seams_are_singletons(): void
    {
        // Register the tstore provider first: isShared() asks the container
        // what the binding IS, so an unregistered key trivially answers false.
        $this->tstoreApp();

        self::assertTrue(
            $this->app->isShared(TstoreCommand::SCHEDULES_KEY),
            'teleclient.tstore.schedules must stay a singleton',
        );
        self::assertTrue(
            $this->app->isShared(NotifyCommand::SCHEDULES_KEY),
            'teleclient.notify.schedules must stay a singleton',
        );
    }

    /**
     * GENERAL FORM OF THE AUDIT'S ORIGINAL FINDING, kept as a guard.
     *
     * The tstore/notify refusal text once told the operator to set
     * teleframe.tstore.schedules=temporal, and following that instruction to
     * the letter changed nothing. Cycle 19 removed the mention instead of
     * wiring the key; this pass wired the key, and the text in
     * Store\Schedules\ (NOT this slice's file) still does not name it.
     *
     * That combination is deliberate, not an oversight: the two keys the text
     * DOES name are the two this package can honestly describe, and adding
     * three more lines of prose to a class that exists to be honest is a
     * separate decision for whoever owns it. What must hold — now and after
     * any future rewire — is the invariant: the refusal may never name a key
     * that is classified DEAD.
     *
     * tests/Store/SchedulePublisherDescriptionTest proves the same thing for
     * specific keys; this one is registry-driven, so a NEW dead key named in
     * that text is caught without anybody remembering to add a case.
     */
    public function test_the_null_publishers_instructions_never_name_a_dead_key(): void
    {
        $text = (new NullSchedulePublisher)->describe();

        preg_match_all('/teleframe\.[a-z0-9_.]+/', $text, $matches);
        $named = array_values(array_unique($matches[0]));

        self::assertNotEmpty($named, 'the refusal must still be an instruction, or this proves nothing');

        $dishonest = array_merge(
            ConfigKeyRegistry::DEAD,
            ConfigKeyRegistry::READ_BUT_IGNORED,
        );

        $offenders = array_values(array_intersect($named, $dishonest));

        self::assertSame(
            [],
            $offenders,
            "NullSchedulePublisher::describe() tells the operator to set a key nothing\n"
            ."reads — a promise that reaches the terminal and cannot be kept:\n  "
            .implode("\n  ", $offenders),
        );
    }

    /**
     * And the complement: the keys the text DOES name must be live, so the
     * instruction is followable. (The refusal names the app and backup drivers;
     * both are wired, and their own suites prove they act.)
     */
    public function test_the_keys_the_null_publisher_does_name_are_classified_as_read(): void
    {
        $text = (new NullSchedulePublisher)->describe();

        preg_match_all('/teleframe\.[a-z0-9_.]+/', $text, $matches);

        $notRead = array_values(array_diff(
            array_unique($matches[0]),
            ConfigKeyRegistry::READ,
        ));

        self::assertSame(
            [],
            $notRead,
            "the refusal names key(s) that are not classified READ:\n  "
            .implode("\n  ", $notRead),
        );
    }

    // --------------------------------------------------------------- plumbing

    private function tstoreSchedules(): SchedulePublisher
    {
        return $this->tstoreApp()->make(TstoreCommand::SCHEDULES_KEY);
    }

    private function notifySchedules(): SchedulePublisher
    {
        return $this->app->make(NotifyCommand::SCHEDULES_KEY);
    }

    /**
     * TeleframeServiceProvider is not among this test's package providers (it
     * is a large provider with its own boot surface), so it is registered onto
     * a THROWAWAY application. The seam under test is one method's binding, and
     * booting the whole provider into the shared app would drag its migrations,
     * views and observers in behind it.
     */
    private function tstoreApp(): Container
    {
        if (! isset($this->tstoreApp)) {
            $app = $this->app;
            (new TeleframeServiceProvider($app))->register();
            $this->tstoreApp = $app;
        }

        return $this->tstoreApp;
    }

    private ?Container $tstoreApp = null;

    private function forgetTstoreSchedules(): void
    {
        $this->tstoreApp = null;
        $this->tstoreApp()->forgetInstance(TstoreCommand::SCHEDULES_KEY);
    }

    private function forgetNotifySchedules(): void
    {
        $this->app->forgetInstance(NotifyCommand::SCHEDULES_KEY);
    }

    /**
     * Pin the resolver the provider will use, with an explicit SDK outcome.
     * null models "temporal/sdk is not installed in this environment".
     */
    private function bindResolverBuilding(?RecurringScheduleClient $client): void
    {
        $this->app->instance(
            TemporalClientResolver::class,
            new TemporalClientResolver(
                null,
                static fn (string $dsn = '', string $namespace = 'default'): ?RecurringScheduleClient => $client,
            ),
        );
    }

    private function fakeRecurringClient(): RecurringScheduleClient
    {
        return self::staticFakeClient();
    }

    private static function staticFakeClient(): RecurringScheduleClient
    {
        return new class implements RecurringScheduleClient
        {
            /** @var list<array{scheduleId: string, activity: string, interval: int, queue: string}> */
            public array $armed = [];

            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                $this->armed[] = [
                    'scheduleId' => $scheduleId,
                    'activity' => $activity,
                    'interval' => $intervalSeconds,
                    'queue' => $taskQueue,
                ];

                return 'sched-'.$scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-should-not-be-used';
            }
        };
    }
}
