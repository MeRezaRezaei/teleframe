<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

use InvalidArgumentException;
use RuntimeException;
use Temporal\Client\Schedule\Action\StartWorkflowAction;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleOptions;
use Temporal\Client\Schedule\Spec\IntervalSpec;
use Temporal\Client\Schedule\Spec\ScheduleSpec;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\DataConverter\EncodedValues;
use Throwable;

/**
 * SdkTemporalScheduleClient — the first in-tree RecurringScheduleClient.
 *
 * WHY THIS FILE EXISTS. Until now the ONLY implementor of RecurringScheduleClient
 * in the whole tree was an anonymous class inside a test, so both
 * `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` and `TELEFRAME_APP_SCHEDULES=temporal`
 * could only ever reach a refusal. The user asked for Temporal for schedules by
 * name ("i prefer ... temporral for shcduls we have them both installed baremetal in
 * this os"), and the SDK is already installed in this repo. So the seam now has a
 * real implementor that actually talks to the SDK.
 *
 * WHICH SDK CLASSES ARE USED (all behind runtime guards, none in a signature):
 *   - Temporal\Client\ScheduleClientInterface — createSchedule(), getHandle()
 *   - Temporal\Client\Schedule\Schedule      — the schedule DTO we hand over
 *   - Temporal\Client\Schedule\Spec\ScheduleSpec + Spec\IntervalSpec — the cadence
 *   - Temporal\Client\Schedule\Action\StartWorkflowAction — the action (activity +
 *     task queue + input), with IdReusePolicy::ALLOW_DUPLICATE
 *   - Temporal\Client\Schedule\ScheduleHandle — describe()/update() from getHandle()
 *   - Temporal\Client\Schedule\ScheduleOptions — namespace on the create path
 *   - Temporal\Client\WorkflowClientInterface — newUntypedWorkflowStub()/start() for
 *     the one-shot startWorkflow() half
 *
 * temporal/sdk is an OPTIONAL RUNTIME DEPENDENCY of this package: it is
 * require-DEV here, on purpose, because the engine must stay installable without
 * Temporal. Concretely, that means:
 *   - no SDK class is named in any signature or property (`object` + method_exists
 *     duck typing), so THIS FILE LOADS FINE WITH THE SDK ABSENT;
 *   - TemporalClientResolver refuses with self::sdkMissingMessage() instead;
 *   - a host that wants this client installs the SDK itself
 *     (`composer require temporal/sdk`) and binds the client into the container.
 *
 * IDEMPOTENCE IS REAL, NOT "create and hope". Temporal's CreateSchedule RPC fails
 * with ALREADY_EXISTS on a second create, so a boot-time install() that ran on
 * every deploy would otherwise either multiply the backup or blow up. This class
 * describes the handle first and only creates when the server says NOT_FOUND;
 * otherwise it UPDATES the one existing schedule in place. Two calls with the same
 * schedule id therefore refresh ONE schedule — proven in
 * tests/Store/SdkTemporalScheduleClientTest.php against the real SDK DTOs, real
 * ScheduleMapper and a stateful double of the SDK's own
 * Temporal\Client\GRPC\ServiceClientInterface.
 *
 * NOT PROVEN: nothing here has been run against a LIVE Temporal server. The
 * request/response shapes are the SDK's own, and the gRPC path is the SDK's, but
 * "the server accepts this schedule and fires it on the cadence" is not verified
 * by this suite. Treat live behaviour as untested.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
final class SdkTemporalScheduleClient implements RecurringScheduleClient
{
    /**
     * gRPC status code for NOT_FOUND (Temporal\Client\GRPC\StatusCode::NOT_FOUND).
     * Spelled as a literal so this file has no SDK symbol in a constant expression.
     */
    private const GRPC_NOT_FOUND = 5;

    public function __construct(
        /**
         * The SDK object doing the talking. Typed `object` on purpose: it may be a
         * Temporal\Client\ScheduleClientInterface, or a Temporal\Client\WorkflowClient
         * that fronts one, and it must stay nameable while the SDK is absent.
         * Nothing is dialled here — construction touches no socket.
         */
        private readonly object $scheduleClient,
        private string $namespace = 'default',
    ) {}

    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * The one-shot half: start a SINGLE workflow execution and return its workflow
     * id. It structurally cannot recur — see TemporalScheduleClient.
     *
     * `$args` is the payload the spec producers emit, which is an associative map
     * (interval_seconds, bucket, …), not a positional argument list. A LIST is
     * spread as positional workflow arguments (the SDK's own calling convention);
     * anything else is passed as a SINGLE map argument, because a workflow declared
     * with a typed signature cannot receive an assoc array unpacked into it.
     */
    public function startWorkflow(string $workflow, string $taskQueue, array $args): string
    {
        $client = $this->workflowClient();
        $options = $this->workflowOptions($taskQueue);
        $stub = $client->newUntypedWorkflowStub($workflow, $options);

        $run = array_is_list($args)
            ? $client->start($stub, ...$args)
            : $client->start($stub, $args);

        $execution = $run->getExecution();

        return method_exists($execution, 'getID')
            ? (string) $execution->getID()
            : (string) $workflow;
    }

    /**
     * Idempotent upsert of ONE recurring schedule, keyed by `$scheduleId`.
     *
     * Existing → handle->update($schedule) (one schedule, refreshed).
     * Absent   → scheduleClient->createSchedule(…, $scheduleId).
     * Either way exactly one schedule named `$scheduleId` exists afterwards.
     *
     * @param  array<string, mixed>  $args
     */
    public function createOrUpdateSchedule(
        string $scheduleId,
        string $activity,
        int $intervalSeconds,
        string $taskQueue,
        array $args = [],
    ): string {
        $scheduleId !== '' or throw new InvalidArgumentException(
            'A Temporal schedule needs a non-empty schedule id; an empty one would be '
            .'regenerated by the SDK as a UUID and defeat idempotence.',
        );

        // A zero/negative cadence is NOT "no cadence" — it is a schedule that fires
        // unthrottled. Refuse loudly instead of coercing it into a 1s default.
        $intervalSeconds > 0 or throw new InvalidArgumentException(sprintf(
            'Refusing to arm Temporal schedule "%s" for %s: intervalSeconds must be a '
            .'POSITIVE integer, got %d. A non-positive interval is not a cadence, it is '
            .'an unthrottled loop; fix the spec instead of coercing it.',
            $scheduleId,
            $activity,
            $intervalSeconds,
        ));

        $client = $this->scheduleClient();
        $schedule = $this->buildSchedule($scheduleId, $activity, $intervalSeconds, $taskQueue, $args);
        $handle = $client->getHandle($scheduleId, $this->namespace);

        if ($this->exists($handle)) {
            // Unconditional update: the conflict token is deliberately NOT threaded
            // through, because the thing being refreshed is this package's own
            // install()-time definition of the schedule, not a user-tweaked one.
            $handle->update($schedule);

            return $scheduleId;
        }

        $created = $client->createSchedule($schedule, $this->scheduleOptions(), $scheduleId);

        return method_exists($created, 'getID') ? (string) $created->getID() : $scheduleId;
    }

    /**
     * Is the named schedule already registered on the server?
     *
     * getHandle() itself dials nothing — it is a value object. describe() is the
     * existence probe, and only a NOT_FOUND answer means "create it"; every other
     * failure (auth, transport, DNS) is rethrown so a broken install cannot be
     * mistaken for a first-time create.
     */
    private function exists(object $handle): bool
    {
        if (! method_exists($handle, 'describe')) {
            return false;
        }

        try {
            $handle->describe();
        } catch (Throwable $e) {
            if ($e->getCode() === self::GRPC_NOT_FOUND) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * The SDK object exposing createSchedule()/getHandle(). A host may hand us a
     * bare Temporal\Client\ScheduleClientInterface, or a workflow client that
     * fronts one; both are accepted, and neither is named in a signature.
     */
    private function scheduleClient(): object
    {
        $client = $this->scheduleClient;

        if (method_exists($client, 'getScheduleClient')) {
            $candidate = $client->getScheduleClient();

            if (is_object($candidate)) {
                $client = $candidate;
            }
        }

        if (! method_exists($client, 'createSchedule') || ! method_exists($client, 'getHandle')) {
            throw new RuntimeException(self::capabilityMessage('createSchedule()+getHandle()', $client));
        }

        return $client;
    }

    /**
     * The SDK object exposing newUntypedWorkflowStub()/start() for the one-shot
     * half. A schedule client alone cannot start a run, so a host that only binds
     * schedules is told so rather than silently doing nothing.
     */
    private function workflowClient(): object
    {
        $client = $this->scheduleClient;

        if (! method_exists($client, 'newUntypedWorkflowStub') || ! method_exists($client, 'start')) {
            throw new RuntimeException(self::capabilityMessage('newUntypedWorkflowStub()+start()', $client));
        }

        return $client;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return object Temporal\Client\Schedule\Schedule
     */
    private function buildSchedule(
        string $scheduleId,
        string $activity,
        int $intervalSeconds,
        string $taskQueue,
        array $args,
    ): object {
        // Resolved here, not in a signature, so this file loads with the SDK absent.
        $spec = ScheduleSpec::new()->withAddedInterval(IntervalSpec::new($intervalSeconds));

        // The action runs the named activity on the named task queue. The workflow
        // id is the schedule id and duplicates are explicitly ALLOWED: a recurring
        // schedule legitimately re-runs the same workflow on every tick, and the
        // server default (Unspecified) would resolve to TERMINATE_IF_RUNNING and kill
        // the run in progress whenever a tick overlapped the last one.
        $action = StartWorkflowAction::new($activity)
            ->withTaskQueue($taskQueue)
            ->withWorkflowId($scheduleId)
            ->withWorkflowIdReusePolicy(IdReusePolicy::AllowDuplicate)
            // Encoded explicitly rather than handed over as a raw array: the spec
            // producers emit an ASSOCIATIVE payload (interval_seconds, bucket, …),
            // and encoding it here keeps it one workflow argument instead of an
            // argument list the workflow's typed signature cannot receive.
            ->withInput(EncodedValues::fromValues([$args]))
            ->withStaticSummary(sprintf('%s every %ds (Teleframe)', $activity, $intervalSeconds));

        return Schedule::new()->withSpec($spec)->withAction($action);
    }

    /** @return object Temporal\Client\WorkflowOptions */
    private function workflowOptions(string $taskQueue): object
    {
        return (new WorkflowOptions)->withTaskQueue($taskQueue);
    }

    /**
     * @return object Temporal\Client\Schedule\ScheduleOptions
     *
     * `withNamespace()` carries a @deprecated tag in the SDK in favour of setting
     * ClientOptions at construction. It is used deliberately: the host may not have
     * built the client with our namespace, and a schedule created in the wrong
     * namespace is worse than a deprecated-but-working call. It emits no runtime
     * deprecation notice (the tag is docblock-only).
     */
    private function scheduleOptions(): object
    {
        return ScheduleOptions::new()->withNamespace($this->namespace);
    }

    private static function capabilityMessage(string $needed, object $client): string
    {
        return sprintf(
            'This Temporal client (%s) does not expose %s, so the schedule could not be armed. '
            .'Bind a temporal/sdk object that implements Temporal\\Client\\ScheduleClientInterface '
            .'(schedules) and Temporal\\Client\\WorkflowClientInterface (one-shot startWorkflow).',
            $client::class,
            $needed,
        );
    }

    /**
     * The refusal a host actually needs to read when the SDK is not there. It names
     * the package and the composer require, because "no client bound" sent people
     * looking for a container binding that did not exist.
     *
     * It does NOT claim Temporal works here: temporal/sdk is require-DEV in this
     * package, and no live Temporal server has been exercised.
     */
    public static function sdkMissingMessage(int $scheduleCount = 0): string
    {
        $dropped = $scheduleCount > 0
            ? sprintf('%d scheduled job(s)', $scheduleCount)
            : 'the configured scheduled job(s)';

        return sprintf(
            'Schedule driver "temporal" is configured but the Temporal PHP SDK is not installed, '
            .'refusing to silently drop %s. temporal/sdk is an OPTIONAL runtime '
            .'dependency of this package (it is require-DEV only, on purpose). To use it: '
            .'composer require temporal/sdk, then bind %s with a Temporal\\Client\\ScheduleClientInterface '
            .'(recurring) or a %s (one-shot only). Until then use driver "null" with the documented '
            .'cron line, or your own scheduler.',
            $dropped,
            self::class,
            TemporalScheduleClient::class,
        );
    }
}
