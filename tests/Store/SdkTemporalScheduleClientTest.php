<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SdkTemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Temporal\Api\Schedule\V1\Schedule as ScheduleMessage;
use Temporal\Api\Workflowservice\V1\CreateScheduleRequest;
use Temporal\Api\Workflowservice\V1\CreateScheduleResponse;
use Temporal\Api\Workflowservice\V1\DescribeScheduleRequest;
use Temporal\Api\Workflowservice\V1\DescribeScheduleResponse;
use Temporal\Api\Workflowservice\V1\UpdateScheduleRequest;
use Temporal\Api\Workflowservice\V1\UpdateScheduleResponse;
use Temporal\Client\ClientOptions;
use Temporal\Client\GRPC\Context;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\ScheduleClient;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowStubInterface;
use Temporal\Common\IdReusePolicy;
use Temporal\Exception\Client\ServiceClientException;
use Temporal\Workflow\WorkflowExecution;
use Temporal\Workflow\WorkflowRunInterface;

/**
 * SdkTemporalScheduleClient — the first in-tree RecurringScheduleClient.
 *
 * The whole point of this slice is that the seam previously had NO implementor
 * outside an anonymous class in a test, so `temporal` could only ever throw. A test
 * that used a hand-rolled double of OUR OWN interface would therefore have proved
 * nothing about idempotence — it would have proved that our own code calls our own
 * double. So nothing here fakes RecurringScheduleClient for the idempotence or
 * cadence proofs.
 *
 * What is REAL in these tests:
 *   - the SDK's own DTOs (Schedule, ScheduleSpec, IntervalSpec, StartWorkflowAction),
 *   - the SDK's own ScheduleMapper and protobuf marshalling,
 *   - the SDK's own ScheduleClient, ScheduleHandle, WorkflowOptions and ServiceClient,
 *   - the SDK's own Temporal\Exception\Client\ServiceClientException, thrown with a
 *     real gRPC NOT_FOUND status for an absent schedule.
 *
 * What is DOUBLED: the single chokepoint every one of those objects talks through —
 * Temporal\Client\GRPC\ServiceClientInterface, the SDK's own gRPC service interface,
 * generated as a PHPUnit double and given STATEFUL server semantics (a real store
 * keyed by schedule id; create on a duplicate answers ALREADY_EXISTS; describe on an
 * absent id answers NOT_FOUND; update replaces in place). That is as close to a
 * server as this suite can honestly get.
 *
 * NOT PROVEN, stated plainly: no LIVE Temporal server is involved. These tests show
 * the requests this package hands the SDK are well-formed, idempotent by id, and
 * carry the stated cadence. They do NOT show the server accepts them, nor that it
 * fires on time. A live gate remains opt-in and separate.
 *
 * ENVIRONMENT INDEPENDENCE. Only setUp() is environment-shaped, and only because
 * the SDK is an optional require-dev dependency: with it absent the whole class
 * skips with the composer require that would un-skip it. No test in here BRANCHES
 * on whether the SDK is installed. The two that cover the resolver's two outcomes
 * pin one arm with the factory seam and let the other accept only the two
 * legitimate outcomes of the real default path, so each is true in CI (no
 * ext-grpc) and on a laptop that has it. The one thing this suite still cannot do
 * in CI is EXECUTE a successful default-path construction, because that genuinely
 * needs ext-grpc — stated here rather than papered over by a branch.
 */
final class SdkTemporalScheduleClientTest extends TestCase
{
    /**
     * scheduleId => the Schedule proto the "server" currently holds for it.
     *
     * @var array<string, ScheduleMessage>
     */
    private array $store = [];

    /** @var list<string> */
    private array $calls = [];

    private static function sdkPresent(): bool
    {
        return class_exists(ScheduleClient::class)
            && interface_exists(ServiceClientInterface::class)
            && class_exists(DescribeScheduleRequest::class);
    }

    protected function setUp(): void
    {
        if (! self::sdkPresent()) {
            self::markTestSkipped(
                'temporal/sdk is an optional (require-DEV) runtime dependency; install it with '
                .'`composer require temporal/sdk` to exercise SdkTemporalScheduleClient.',
            );
        }

        $this->store = [];
        $this->calls = [];
    }

    public function test_it_satisfies_the_recurring_seam(): void
    {
        self::assertInstanceOf(RecurringScheduleClient::class, $this->client());
    }

    public function test_constructing_the_client_dials_nothing(): void
    {
        // A client that dialled in its constructor would open a queue connection
        // during a Laravel boot and break plain-PHP construction.
        $client = $this->client();

        self::assertInstanceOf(SdkTemporalScheduleClient::class, $client);
        self::assertSame([], $this->calls, 'the constructor issued an RPC');
        self::assertSame([], $this->store, 'the constructor registered a schedule');
    }

    public function test_two_calls_with_the_same_id_produce_one_schedule(): void
    {
        $client = $this->client();

        $first = $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');
        $second = $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');

        // One schedule, not two. This is the whole boot-time-install() story: a
        // deploy that re-runs install() must refresh, never stack.
        self::assertCount(1, $this->store);
        self::assertSame('tstore-backup', $first);
        self::assertSame('tstore-backup', $second);
        self::assertSame(['tstore-backup'], array_keys($this->store));

        // The second call went describe() -> update(), never create().
        self::assertSame(
            ['DescribeSchedule', 'CreateSchedule', 'DescribeSchedule', 'UpdateSchedule'],
            $this->calls,
        );
    }

    public function test_upsert_refreshes_the_existing_schedule_in_place(): void
    {
        $client = $this->client();

        $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');
        $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 1800, 'tstore');

        self::assertCount(1, $this->store, 'a cadence change must not create a second schedule');
        self::assertSame(1800, $this->intervalSecondsOf('tstore-backup'));
    }

    public function test_the_interval_reaching_the_sdk_equals_interval_seconds(): void
    {
        foreach ([900, 1, 86400] as $seconds) {
            $client = $this->client();

            $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', $seconds, 'tstore');

            self::assertSame(
                $seconds,
                $this->intervalSecondsOf('tstore-backup'),
                "intervalSeconds={$seconds} did not reach the SDK intact",
            );
        }
    }

    public function test_the_interval_is_one_interval_spec_not_a_cron_or_a_coerced_default(): void
    {
        $client = $this->client();

        $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');

        $spec = $this->store['tstore-backup']->getSpec();
        $intervals = iterator_to_array($spec->getInterval());

        self::assertCount(1, $intervals);
        self::assertSame(0, $intervals[0]->getPhase()->getSeconds(), 'phase must be zero');
        // Read the proto's own duration, not the DateInterval "%s" component, which
        // only prints the sub-minute part (90s would "look like" 30s).
        self::assertSame(900, (int) $intervals[0]->getInterval()->getSeconds());
        self::assertCount(0, iterator_to_array($this->store['tstore-backup']->getSpec()->getCalendar()));
    }

    public function test_the_action_carries_the_activity_queue_id_and_duplicate_policy(): void
    {
        $client = $this->client();

        $client->createOrUpdateSchedule(
            'tstore-backup',
            'teleframe:tstore backup',
            900,
            'tstore',
            ['bucket' => 'media'],
        );

        $action = $this->store['tstore-backup']->getAction()->getStartWorkflow();

        self::assertSame('teleframe:tstore backup', $action->getWorkflowType()->getName());
        self::assertSame('tstore', $action->getTaskQueue()->getName());
        self::assertSame('tstore-backup', $action->getWorkflowId());
        self::assertSame(
            IdReusePolicy::POLICY_ALLOW_DUPLICATE,
            $action->getWorkflowIdReusePolicy(),
            'a recurring action must be allowed to re-run while a previous run is still open',
        );
    }

    public function test_a_non_positive_interval_is_refused_loudly_before_any_rpc(): void
    {
        $client = $this->client();

        foreach ([0, -1] as $seconds) {
            try {
                $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', $seconds, 'tstore');
                self::fail("intervalSeconds={$seconds} was accepted; a non-positive cadence must be refused");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('POSITIVE integer', $e->getMessage());
                self::assertStringContainsString((string) $seconds, $e->getMessage());
            }
        }

        self::assertSame([], $this->calls, 'a refused cadence must not reach the SDK');
        self::assertSame([], $this->store);
    }

    public function test_an_empty_schedule_id_is_refused(): void
    {
        $client = $this->client();

        $this->expectException(InvalidArgumentException::class);

        $client->createOrUpdateSchedule('', 'teleframe:tstore backup', 900, 'tstore');
    }

    public function test_start_workflow_returns_the_execution_id(): void
    {
        $workflowClient = $this->createStub(WorkflowClientInterface::class);
        $workflowClient->method('newUntypedWorkflowStub')
            ->willReturn($this->createStub(WorkflowStubInterface::class));
        $workflowClient->method('start')->willReturn($this->runReturning('wf-42'));

        $client = new SdkTemporalScheduleClient($workflowClient, 'default');

        self::assertSame('wf-42', $client->startWorkflow('Backup', 'tstore', ['bucket' => 'media']));
    }

    public function test_the_publisher_arms_a_real_recurring_schedule_through_this_client(): void
    {
        // End to end, without faking our own seam: the real
        // TemporalSchedulePublisher + the real SdkTemporalScheduleClient + the real
        // SDK ScheduleClient, over the stateful service-client double.
        $publisher = new TemporalSchedulePublisher(
            '127.0.0.1:7233',
            $this->client(),
            'tstore',
        );

        self::assertTrue($publisher->armsRecurring());

        $status = $publisher->install([
            'tstore-backup' => [
                'activity' => 'teleframe:tstore backup',
                'interval_seconds' => 900,
                'task_queue' => 'tstore',
                'args' => ['bucket' => 'media'],
            ],
        ]);

        // install() twice = one schedule, exactly as a redeploy would.
        $publisher->install([
            'tstore-backup' => [
                'activity' => 'teleframe:tstore backup',
                'interval_seconds' => 900,
                'task_queue' => 'tstore',
                'args' => ['bucket' => 'media'],
            ],
        ]);

        self::assertCount(1, $this->store);
        self::assertSame(900, $this->intervalSecondsOf('tstore-backup'));
        self::assertStringContainsString('armed', $status['tstore-backup']);
        self::assertStringContainsString('every 900s', $status['tstore-backup']);
    }

    public function test_the_missing_sdk_message_names_the_package_and_the_require(): void
    {
        $message = SdkTemporalScheduleClient::sdkMissingMessage();
        $counted = SdkTemporalScheduleClient::sdkMissingMessage(3);

        self::assertStringContainsString('temporal/sdk', $message);
        self::assertStringContainsString('composer require temporal/sdk', $message);
        self::assertStringContainsString('optional', strtolower($message));
        self::assertStringContainsString('3 scheduled job(s)', $counted);
        // It must NOT read as "bind a client you already had" — the SDK ships now.
        self::assertStringNotContainsString('The host must bind', $message);
    }

    public function test_the_resolver_hands_back_the_host_bound_client(): void
    {
        $hostBound = new SdkTemporalScheduleClient($this->client());
        $resolver = new TemporalClientResolver($hostBound);

        self::assertSame($hostBound, $resolver->hostBound());
        self::assertSame($hostBound, $resolver->resolve('127.0.0.1:7233'));
        self::assertSame(
            $hostBound,
            $resolver->resolve('127.0.0.1:7233', 'other-namespace'),
            'the host-bound client must win over the SDK path, namespace and DSN included',
        );
        self::assertSame([], $this->calls, 'resolving a host-bound client must not touch the SDK');
    }

    public function test_the_resolver_prefers_the_host_client_over_an_available_sdk(): void
    {
        $hostBound = new SdkTemporalScheduleClient($this->client());

        $resolver = new TemporalClientResolver(
            $hostBound,
            fn (): RecurringScheduleClient => new SdkTemporalScheduleClient($this->client(), 'from-factory'),
        );

        self::assertSame($hostBound, $resolver->resolve('127.0.0.1:7233'));
    }

    public function test_the_resolver_uses_the_sdk_when_no_host_client_exists(): void
    {
        $resolver = new TemporalClientResolver(
            null,
            fn (string $dsn, string $namespace): RecurringScheduleClient => $this->client($namespace),
        );

        $resolved = $resolver->resolve('127.0.0.1:7233', 'teleframe');

        self::assertInstanceOf(SdkTemporalScheduleClient::class, $resolved);
        self::assertSame('teleframe', $resolved->namespace());
    }

    public function test_the_resolver_throws_the_named_message_when_the_sdk_is_unavailable(): void
    {
        // A null-returning factory is exactly "the optional SDK is not usable here":
        // the resolver must refuse, not return null and let a silent no-op publisher
        // report success.
        $resolver = new TemporalClientResolver(null, fn (): ?RecurringScheduleClient => null);

        try {
            $resolver->resolve('127.0.0.1:7233');
            self::fail('the resolver returned a client for an unavailable SDK');
        } catch (RuntimeException $e) {
            self::assertSame(SdkTemporalScheduleClient::sdkMissingMessage(), $e->getMessage());
            self::assertStringContainsString('temporal/sdk', $e->getMessage());
            self::assertStringContainsString('composer require temporal/sdk', $e->getMessage());
        }
    }

    public function test_the_refusal_is_the_named_message_in_every_environment(): void
    {
        // DETERMINISTIC ARM. The availability of temporal/sdk and ext-grpc is a
        // property of the HOST (require-dev here; the CI image has no
        // ext-grpc), so a test that let the environment pick its branch passed
        // and failed for reasons that had nothing to do with the code. The
        // factory seam pins the outcome, so this arm is the SAME in CI, on a
        // laptop with grpc, and on a host that installed the SDK itself: a null
        // factory is exactly "the SDK is not usable here", and the resolver must
        // refuse — never return null and let a no-op publisher report success.
        $resolver = new TemporalClientResolver(null, fn (): ?RecurringScheduleClient => null);

        try {
            $resolver->resolve('127.0.0.1:7233');
            self::fail('the resolver returned a client for an unavailable SDK');
        } catch (RuntimeException $e) {
            self::assertSame(SdkTemporalScheduleClient::sdkMissingMessage(), $e->getMessage());
            self::assertStringContainsString('temporal/sdk', $e->getMessage());
            self::assertStringContainsString('composer require temporal/sdk', $e->getMessage());
        }
    }

    public function test_the_default_sdk_path_either_answers_with_a_client_or_refuses_with_that_message(): void
    {
        // The OTHER arm, and the only test here that touches the real
        // buildFromSdk(). It is written so the ENVIRONMENT cannot choose what it
        // asserts: there are exactly two acceptable outcomes and no third. On CI
        // (no ext-grpc) it proves the refusal; on a host with the SDK it
        // exercises the real construction path end to end and proves the
        // namespace it was asked for is the namespace it got. What it would
        // catch — a null return, a wrong type, or a different message — is
        // environment-independent.
        //
        // NOT PROVEN either way: that a live Temporal server accepts what this
        // builds. Nothing here dials (ServiceClient::create() wraps a lazy
        // connection factory), and the namespace is not a connection.
        try {
            $resolved = (new TemporalClientResolver)->resolve('127.0.0.1:7233', 'teleframe');

            self::assertInstanceOf(SdkTemporalScheduleClient::class, $resolved);
            self::assertSame('teleframe', $resolved->namespace());
        } catch (RuntimeException $e) {
            self::assertSame(
                SdkTemporalScheduleClient::sdkMissingMessage(),
                $e->getMessage(),
                'the default path must refuse with THAT message, not some other failure that reads like a config error',
            );
        }
    }

    public function test_the_sdk_probe_reports_the_facts_it_checks_instead_of_guessing(): void
    {
        // The probe is a pure function of two environment facts, so this
        // assertion is TRUE in every environment — it does not branch, and it
        // fails the moment sdkInstalled() starts answering from anything else
        // (a hardcoded true, a cached probe, a config flag).
        self::assertSame(
            class_exists(ScheduleClient::class)
                && class_exists(ServiceClient::class)
                && extension_loaded('grpc'),
            TemporalClientResolver::sdkInstalled(),
        );
    }

    public function test_a_client_without_the_schedule_surface_is_refused_by_name(): void
    {
        // Duck typing must fail LOUDLY, not silently: a bare workflow client cannot
        // arm a cadence, and pretending otherwise is the false-green this slice kills.
        $workflowOnly = $this->createStub(WorkflowClientInterface::class);

        $client = new SdkTemporalScheduleClient($workflowOnly);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('createSchedule()+getHandle()');

        $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');
    }

    public function test_a_schedule_only_client_says_so_instead_of_starting_nothing(): void
    {
        $scheduleOnly = new \stdClass;

        $client = new SdkTemporalScheduleClient($scheduleOnly);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('newUntypedWorkflowStub()+start()');

        $client->startWorkflow('Backup', 'tstore', []);
    }

    // ---------------------------------------------------------------- doubles --

    private function client(string $namespace = 'default'): SdkTemporalScheduleClient
    {
        // The real SDK ScheduleClient, over the stateful service-client double.
        $serviceClient = ScheduleClient::create(
            $this->serviceClient(),
            (new ClientOptions)->withNamespace($namespace),
        );

        return new SdkTemporalScheduleClient($serviceClient, $namespace);
    }

    /**
     * A stateful double of the SDK's own gRPC service interface — the one chokepoint
     * every SDK client funnels through. Semantics mirror the Temporal server for the
     * three schedule RPCs this client uses.
     */
    private function serviceClient(): ServiceClientInterface
    {
        $service = $this->createStub(ServiceClientInterface::class);
        $service->method('getContext')->willReturn(Context::default());
        $service->method('withContext')->willReturnSelf();
        $service->method('getServerCapabilities')->willReturn(null);

        $service->method('CreateSchedule')->willReturnCallback(
            function (CreateScheduleRequest $request): CreateScheduleResponse {
                $this->calls[] = 'CreateSchedule';

                $id = $request->getScheduleId();

                if (isset($this->store[$id])) {
                    throw $this->grpcError(6, "schedule already exists: {$id}");
                }

                $this->store[$id] = $request->getSchedule();

                return new CreateScheduleResponse;
            },
        );

        $service->method('DescribeSchedule')->willReturnCallback(
            function (DescribeScheduleRequest $request): DescribeScheduleResponse {
                $this->calls[] = 'DescribeSchedule';

                $id = $request->getScheduleId();

                if (! isset($this->store[$id])) {
                    throw $this->grpcError(5, "schedule not found: {$id}");
                }

                return (new DescribeScheduleResponse)
                    ->setSchedule($this->store[$id])
                    ->setConflictToken("conflict-token:{$id}");
            },
        );

        $service->method('UpdateSchedule')->willReturnCallback(
            function (UpdateScheduleRequest $request): UpdateScheduleResponse {
                $this->calls[] = 'UpdateSchedule';

                $id = $request->getScheduleId();

                if (! isset($this->store[$id])) {
                    throw $this->grpcError(5, "schedule not found: {$id}");
                }

                $this->store[$id] = $request->getSchedule();

                return new UpdateScheduleResponse;
            },
        );

        return $service;
    }

    /** gRPC status codes: 5 = NOT_FOUND, 6 = ALREADY_EXISTS. */
    private function grpcError(int $code, string $details): ServiceClientException
    {
        $status = new \stdClass;
        $status->code = $code;
        $status->details = $details;
        $status->metadata = [];

        return new ServiceClientException($status);
    }

    private function runReturning(string $workflowId): WorkflowRunInterface
    {
        $run = $this->createStub(WorkflowRunInterface::class);
        $run->method('getExecution')->willReturn(new WorkflowExecution($workflowId, 'run-1'));

        return $run;
    }

    /** The cadence the "server" is actually holding, read back from the proto. */
    private function intervalSecondsOf(string $scheduleId): int
    {
        self::assertArrayHasKey($scheduleId, $this->store);

        $intervals = iterator_to_array($this->store[$scheduleId]->getSpec()->getInterval());

        self::assertCount(1, $intervals, 'exactly one interval spec is expected');

        return (int) $intervals[0]->getInterval()->getSeconds();
    }
}
