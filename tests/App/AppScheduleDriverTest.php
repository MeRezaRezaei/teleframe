<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use RuntimeException;

/**
 * The APP side of the recurring-call schedule seam — the twin of
 * BackupSchedulePublisherTest, and the second half of the user's stated
 * preference ("temporral for shcduls we have them both installed baremetal").
 *
 * Before: `TELEFRAME_APP_SCHEDULES=temporal` built a publisher with NO client
 * and the publisher never built one, so the ONLY reachable outcome was the
 * no-client throw and no app call ever recurred. Now the provider routes the
 * driver through TemporalClientResolver: a host-bound client wins verbatim,
 * the package's optional SDK-backed client answers when temporal/sdk is
 * installed, and with neither the resolver refuses BY NAME at resolve time.
 *
 * What these tests pin, so it cannot regress into a green lie:
 *   1. 'null' is still the shipped default (no behaviour change for a host);
 *   2. 'temporal' with neither client nor SDK refuses, naming temporal/sdk —
 *      it never degrades to the null publisher, which would report 'skipped'
 *      while the host believes its calls recurred;
 *   3. a host-bound client still WINS (backward compatibility);
 *   4. an unknown driver fails loudly;
 *   5. nothing dials at construct time (standalone-smoke gate).
 */
final class AppScheduleDriverTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [AppServiceProvider::class];
    }

    private function resolve(): SchedulePublisher
    {
        return $this->app->make(AppSupport::SCHEDULES_KEY);
    }

    /**
     * Pin the resolver with an explicit SDK outcome. null models
     * "temporal/sdk is not installed in this environment" — the CI image has
     * no ext-grpc, so letting the ENVIRONMENT pick the branch made these tests
     * pass and fail for reasons unrelated to the code.
     */
    private function bindResolverBuilding(?RecurringScheduleClient $client): void
    {
        $this->app->instance(
            TemporalClientResolver::class,
            new TemporalClientResolver(null, static fn (): ?RecurringScheduleClient => $client),
        );
    }

    public function test_the_default_driver_still_produces_the_null_publisher(): void
    {
        self::assertInstanceOf(NullSchedulePublisher::class, $this->resolve());
    }

    public function test_temporal_without_any_host_code_arms_a_recurring_client_from_the_optional_sdk(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.app.schedules', 'temporal');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        // Cycle 21. This USED to be a refusal test, green only because the
        // package shipped no client at all. The seam now answers with a real
        // recurring client and nothing written by the host.
        $publisher = $this->resolve();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertTrue($publisher->armsRecurring(), 'a cadence must be armable, not merely startable');
    }

    public function test_the_temporal_driver_never_degrades_to_the_null_publisher(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.app.schedules', 'temporal');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        // A silently-disabled schedule while the host believes its calls recur
        // is the worst available failure: either a real publisher, or a named
        // refusal. Never a null that reports 'skipped'.
        $publisher = $this->resolve();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertNotInstanceOf(NullSchedulePublisher::class, $publisher);
    }

    public function test_temporal_refuses_by_name_when_the_sdk_is_unavailable(): void
    {
        $this->bindResolverBuilding(null);
        $this->app['config']->set('teleframe.app.schedules', 'temporal');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('temporal/sdk');

        $this->resolve();
    }

    public function test_a_host_bound_recurring_client_wins_and_the_app_call_actually_recurs(): void
    {
        $client = $this->fakeRecurringClient();
        $this->app->instance(TemporalScheduleClient::class, $client);
        $this->app['config']->set('teleframe.app.schedules', 'temporal');
        $this->app['config']->set('teleframe.app.schedules_dsn', '10.0.0.9:7233');
        $this->app['config']->set('teleframe.app.schedules_task_queue', 'app-calls');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        $publisher = $this->resolve();
        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertSame('10.0.0.9:7233', $publisher->dsn(), 'the configured DSN is the one used');

        $app = AppDefinition::define('echo-bot', AppMode::Bot, [], [
            AppCall::every('status-ping', 1800, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']),
        ]);

        $status = $publisher->install((new AppCallSpec)->installSpec($app));

        $callId = (new AppCallSpec)->callId($app, $app->calls()[0]);

        self::assertCount(1, $client->armed, 'the HOST client must be the one used');
        self::assertSame($callId, $client->armed[0]['scheduleId']);
        self::assertSame(1800, $client->armed[0]['interval']);
        self::assertSame('app-calls', $client->armed[0]['queue']);
        self::assertStringContainsString('recurring every 1800s', $status[$callId]);
    }

    public function test_two_apps_with_the_same_call_name_do_not_replace_each_other_s_schedule(): void
    {
        // The id is a function of the (app, call) PAIR, so two apps declaring
        // `status-ping` install TWO schedules. Before the id carried the app
        // name, app B's install() upserted app A's schedule in place — app A's
        // cadence and activity silently replaced, with nothing reporting it.
        $client = $this->fakeRecurringClient();
        $this->app->instance(TemporalScheduleClient::class, $client);
        $this->app['config']->set('teleframe.app.schedules', 'temporal');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        $spec = new AppCallSpec;
        $alpha = AppDefinition::define('alpha-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops']),
        ]);
        $beta = AppDefinition::define('beta-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage', ['peer' => '@ops']),
        ]);

        $status = $this->resolve()->install($spec->installSpec($alpha) + $spec->installSpec($beta));

        $alphaId = $spec->callId($alpha, $alpha->calls()[0]);
        $betaId = $spec->callId($beta, $beta->calls()[0]);

        self::assertNotSame($alphaId, $betaId);
        self::assertCount(2, $client->armed, 'each app call must arm its OWN schedule');
        self::assertSame([$alphaId, $betaId], array_column($client->armed, 'scheduleId'));
        self::assertSame([3600, 60], array_column($client->armed, 'interval'),
            "neither app's cadence may be overwritten by the other");
        self::assertArrayHasKey($alphaId, $status);
        self::assertArrayHasKey($betaId, $status);
    }

    public function test_an_unknown_driver_fails_loudly_instead_of_disabling_the_calls(): void
    {
        $this->app['config']->set('teleframe.app.schedules', 'carrier-pigeon');
        $this->app->forgetInstance(AppSupport::SCHEDULES_KEY);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected null|temporal');

        $this->resolve();
    }

    public function test_binding_the_publisher_never_dials_temporal(): void
    {
        // Constructing the publisher opens nothing: a boot-time dial would open
        // a queue connection during a Laravel boot and break the smoke gate.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233');

        self::assertSame('127.0.0.1:7233', $publisher->dsn());
    }

    private function fakeRecurringClient(): RecurringScheduleClient
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
