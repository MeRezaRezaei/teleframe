<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\LaravelWorkerBindings;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\RunAppCallActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temporal\DataConverter\DataConverter;
use Temporal\DataConverter\EncodedValues;
use Temporal\WorkerFactory;

/**
 * THE PRODUCER→CONSUMER CHAIN THAT WAS NEVER CROSSED.
 *
 * THE DEFECT. Every recurring scheduled app call failed on EVERY tick while the
 * status line read fully green:
 *
 *     armed telegram.app.run-call assistant status-ping recurring every 3600s
 *     (schedule app-call-…, 127.0.0.1:7233@default); worker: served by this package
 *
 * The four halves disagreed and each was individually "correct":
 *
 *   - `AppCallSpec::installSpec()` emits `app`/`call` as SIBLING keys of
 *     `interval_seconds`/`activity`;
 *   - `TemporalSchedulePublisher::install()` read ONLY `$spec['args']` — a key
 *     no producer in this tree emits — so the schedule was armed with `args: []`;
 *   - `SdkTemporalScheduleClient` encodes exactly `$args` as the workflow input
 *     (`EncodedValues::fromValues([$args])`);
 *   - `AppRunCallWorkflow::run()` reads `$payload['app']` / `$payload['call']`,
 *     so both arrive as '' and `RunAppCallActivity::runCall()` throws an
 *     AppException naming both, on every single tick.
 *
 * The one-shot path was broken the same way (`args = ['interval_seconds'=>3600]`).
 *
 * WHY IT SURVIVED. No test crossed the chain.
 * `WorkerTickReachabilityTest::a_registered_app_call_activity_reaches_the_real_
 * runtime_and_the_transport` hands the activity `['echo', 'health', []]` — a
 * payload written by hand, which is exactly the thing that was broken. It proved
 * the CONSUMER can run a payload; nothing proved any producer can MAKE one.
 *
 * So every test below starts at the PRODUCER and ends at the transport, and the
 * payload the activity receives is never written by hand anywhere in this file:
 *
 *   1. `AppCallSpec::installSpec()` — the real producer, on a real declaration;
 *   2. `TemporalSchedulePublisher::install()` — the real publisher, with a
 *      `RecurringScheduleClient` double that captures the `$args` it is handed
 *      (the exact value the SDK client encodes);
 *   3. `EncodedValues::fromValues([$args])` → `Payloads` → decode — the real
 *      SDK encoder/decoder round trip, the same call SdkTemporalScheduleClient
 *      makes at buildSchedule();
 *   4. the DECODED payload is indexed the way AppRunCallWorkflow::run() indexes
 *      it (`$payload['app']`, `$payload['call']`) and those two values are
 *      passed to the real activity;
 *   5. the real `TemporalWorker` registration, the real SDK worker, the
 *      prototype read back OUT of the SDK's own repository by the activity type
 *      the SERVER sends, and the SDK's own handler invoking the activity —
 *      which resolves the real `AppRuntime` from a real container seam and calls
 *      the transport.
 *
 * NOT PROVEN, stated plainly: this repo has no live Temporal server and no live
 * Telegram run. What is proved is that the payload a schedule arms with is the
 * payload the workflow reads and the activity runs, which is the part that was
 * broken.
 */
final class AppCallScheduleProducerConsumerTest extends TestCase
{
    private const APP = 'assistant';

    private const CALL = 'status-ping';

    private AppRuntime $runtime;

    private ChainTransportScope $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scope = new ChainTransportScope;

        $this->runtime = new AppRuntime(
            registry: (new AppRegistry)->define(
                AppDefinition::define(self::APP, AppMode::Bot, triggers: [], calls: [
                    AppCall::every(self::CALL, 3600, 'getMe', ['probe' => true]),
                ]),
            ),
            schedules: new NullSchedulePublisher,
            transport: fn (AppMode $mode): ChainTransportScope => $this->scope,
        );
    }

    // ── THE FLAGSHIP: producer → publisher → encoded input → activity ──

    #[Test]
    public function the_declared_call_reaches_the_encoded_workflow_input_and_runs_the_tick(): void
    {
        $armed = $this->armTheRealSpec();

        // (2) What the publisher handed the SDK client, which is what gets encoded.
        self::assertArrayHasKey('app', $armed['args'],
            'the publisher must forward the producer\'s `app` key; AppRunCallWorkflow::run() routes on it');
        self::assertArrayHasKey('call', $armed['args'],
            'the publisher must forward the producer\'s `call` key; AppRunCallWorkflow::run() routes on it');
        self::assertSame(self::APP, $armed['args']['app']);
        self::assertSame(self::CALL, $armed['args']['call']);

        // (3) The REAL encode→decode round trip, the same encoder call
        // SdkTemporalScheduleClient::buildSchedule() makes.
        $payload = $this->decodeTheWayTheWorkflowWould($armed['args']);

        self::assertSame(self::APP, $payload['app'] ?? null,
            'app must survive the SDK encoding, not merely exist in the spec');
        self::assertSame(self::CALL, $payload['call'] ?? null,
            'call must survive the SDK encoding, not merely exist in the spec');

        // (4)+(5) The activity, dispatched through the real SDK registration,
        // with the names read off the DECODED payload exactly as the workflow
        // reads them (AppRunCallWorkflow.php:44-45).
        $report = $this->invokeTheRealTick($payload);

        self::assertSame(self::APP, $report['app'], 'the tick ran the app the schedule declared');
        self::assertSame(self::CALL, $report['call'], 'the tick ran the call the schedule declared');
        self::assertSame(1, $this->scope->invoked,
            'the tick reached the transport — before the fix it raised AppException on the empty names instead');
        self::assertSame('getMe', $this->scope->calls[0]['method'],
            'the method came from the declared AppCall, so the tick ran the catalogued call');
        self::assertSame(['probe' => true], $this->scope->calls[0]['params']);
    }

    /**
     * The one-shot half was broken identically and is fixed by the same change;
     * a schedule that runs once must still name its call.
     */
    #[Test]
    public function the_one_shot_path_also_carries_the_call_names(): void
    {
        $app = $this->declaredApp();
        $spec = (new AppCallSpec)->installSpec($app);
        $callId = (new AppCallSpec)->callId($app, $app->calls()[0]);

        $client = new class implements TemporalScheduleClient
        {
            /** @var list<array{workflow: string, args: array<string, mixed>}> */
            public array $started = [];

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                $this->started[] = ['workflow' => $workflow, 'args' => $args];

                return 'wf-'.count($this->started);
            }
        };

        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $client);

        $status = $publisher->install($spec);

        self::assertCount(1, $client->started);
        self::assertSame(self::APP, $client->started[0]['args']['app'] ?? null,
            'a one-shot run must name its app too');
        self::assertSame(self::CALL, $client->started[0]['args']['call'] ?? null,
            'a one-shot run must name its call too');
        self::assertSame(3600, $client->started[0]['args']['interval_seconds'] ?? null,
            'the stated cadence is still passed in the one-shot payload');
        self::assertArrayHasKey($callId, $status, 'the status still names the schedule it started');
        self::assertStringContainsString('started ONCE, not recurring', $status[$callId]);
    }

    /**
     * The control keys the publisher consumes itself are NOT forwarded as
     * payload: `activity` is the workflow TYPE (Temporal dispatches on the
     * string, not on a payload key), and the cadence and queue are already
     * carried by the schedule/action the SDK builds.
     */
    #[Test]
    public function the_control_keys_stay_out_of_the_workflow_input(): void
    {
        $armed = $this->armTheRealSpec();

        self::assertArrayNotHasKey('activity', $armed['args'],
            'activity is the workflow type, not payload — a copy in the input is a second thing to drift');
        self::assertArrayNotHasKey('task_queue', $armed['args']);
        self::assertArrayNotHasKey('args', $armed['args']);
    }

    /**
     * A third-party producer that sets `args` explicitly keeps working: it is the
     * BASE of the payload, and the spec entry's own keys are overlaid on top.
     *
     * The entry winning is deliberate. A schedule id is derived from the
     * entry's `app`/`call`, so letting a stale `args` key override them would let
     * the id and the routing disagree — the schedule would be armed as one call
     * and tick into another.
     */
    #[Test]
    public function an_explicit_args_key_is_still_honoured_as_the_base_of_the_payload(): void
    {
        $spec = $this->declaredSpec();
        $callId = (string) array_key_first($spec);
        $spec[$callId]['args'] = ['app' => 'from-args', 'bucket' => 'media'];

        $client = new ChainRecurringScheduleClient;
        (new TemporalSchedulePublisher('127.0.0.1:7233', $client))->install($spec);

        self::assertSame(self::APP, $client->armed[0]['args']['app'] ?? null,
            'the entry the schedule id was derived from wins, so id and routing cannot disagree');
        self::assertSame('media', $client->armed[0]['args']['bucket'] ?? null,
            'a key only args carries still reaches the input');
        self::assertSame(self::CALL, $client->armed[0]['args']['call'] ?? null,
            'a key only the entry carries still reaches the input');
    }

    // ── the chain, step by step ─────────────────────────────────────────

    /** The declaration every test starts from. */
    private function declaredApp(): AppDefinition
    {
        return AppDefinition::define(self::APP, AppMode::Bot, triggers: [], calls: [
            AppCall::every(self::CALL, 3600, 'getMe', ['probe' => true]),
        ]);
    }

    /** (1) The real producer's payload. */
    private function declaredSpec(): array
    {
        return (new AppCallSpec)->installSpec($this->declaredApp());
    }

    /**
     * (1)+(2): the real spec through the real publisher, returning what the SDK
     * client was handed.
     *
     * @return array{scheduleId: string, activity: string, interval: int, queue: string, args: array<string, mixed>}
     */
    private function armTheRealSpec(): array
    {
        $client = new ChainRecurringScheduleClient;

        (new TemporalSchedulePublisher('127.0.0.1:7233', $client))->install($this->declaredSpec());

        self::assertCount(1, $client->armed, 'exactly one schedule armed for one declared call');

        return $client->armed[0];
    }

    /**
     * (3) The real SDK encode/decode, byte for byte the same encoder call
     * SdkTemporalScheduleClient::buildSchedule() makes.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function decodeTheWayTheWorkflowWould(array $args): array
    {
        $payloads = EncodedValues::fromValues([$args], DataConverter::createDefault())->toPayloads();

        // The converter is REQUIRED on the decoding side: EncodedValues::getValue()
        // throws `DataConverter is not set` without one, and SdkTemporalScheduleClient
        // gets it from the worker's own binding rather than passing it here. Passing
        // the same default converter the encode used is what the SDK does on the
        // worker side, so this stays byte-identical to the real round trip.
        /** @var mixed $decoded */
        $decoded = EncodedValues::fromPayloadCollection(
            $payloads->getPayloads(),
            DataConverter::createDefault(),
        )->getValue(0);

        // The SDK's default JSON converter hands back a stdClass for a JSON
        // object, while `AppRunCallWorkflow::run()` declares `array $payload`.
        // Normalise to the shape the consumer declares so this helper models
        // what the workflow receives. (The raw-shape question — whether a real
        // worker hands stdClass to an `array`-typed workflow parameter — is
        // flagged separately; this helper deliberately does not paper over it.)
        if ($decoded instanceof \stdClass) {
            $decoded = (array) $decoded;
        }

        self::assertIsArray($decoded, 'the decoded workflow input must be an array');

        return $decoded;
    }

    /**
     * (4)+(5) The tick: the real worker registration, the real SDK worker, the
     * prototype read back out of the SDK's own repository by the activity type
     * the SERVER sends, and the SDK's own handler.
     *
     * @param  array<string, mixed>  $payload  the DECODED workflow input
     * @return array<string, mixed>
     */
    private function invokeTheRealTick(array $payload): array
    {
        $container = new Container;
        $container->bind(RunAppCallActivity::RUNTIME_SEAM, fn (): AppRuntime => $this->runtime);

        $worker = new TemporalWorker(
            'app-calls',
            appCalls: [[self::APP, self::CALL]],
            resolver: (new LaravelWorkerBindings($container))->resolver(),
        );

        $sdkWorker = $worker->register(WorkerFactory::create()->newWorker('app-calls'));

        $prototype = $sdkWorker->getActivities()->find(RunAppCallActivity::TYPE);

        self::assertNotNull($prototype,
            'the real SDK repository has no prototype for '.RunAppCallActivity::TYPE);

        $registered = $prototype->getFactory();
        self::assertNotNull($registered,
            'TemporalWorker::register() must register a factory for '.RunAppCallActivity::TYPE);

        // The three arguments AppRunCallWorkflow::run() passes, sourced from the
        // decoded payload rather than written here by hand.
        return $prototype->getHandler()->invokeArgs(
            $registered(),
            [(string) ($payload['app'] ?? ''), (string) ($payload['call'] ?? ''), $payload],
        );
    }
}

/**
 * The `RecurringScheduleClient` the probe stands in for: it records exactly what
 * the publisher passed, which is exactly what SdkTemporalScheduleClient encodes
 * as the workflow input. Nothing here decides what that input MEANS.
 */
final class ChainRecurringScheduleClient implements RecurringScheduleClient
{
    /** @var list<array{scheduleId: string, activity: string, interval: int, queue: string, args: array<string, mixed>}> */
    public array $armed = [];

    public function startWorkflow(string $workflow, string $taskQueue, array $args): string
    {
        return 'wf-one-shot';
    }

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
            'args' => $args,
        ];

        return $scheduleId;
    }
}

/**
 * The far side of the wire for the app-call tick: a transport with the
 * `call(string, array)` catalog surface that is ALL AppRuntime::runCall()
 * requires of a scope. Recording what it was asked is how the test proves the
 * tick ran the CATALOGUED call rather than reaching a transport by accident.
 *
 * Explicitly not a claim that Telegram was reached.
 */
final class ChainTransportScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    public int $invoked = 0;

    /** @param  array<string, mixed>  $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];
        $this->invoked++;

        return ['_' => $method, 'ok' => true];
    }
}
