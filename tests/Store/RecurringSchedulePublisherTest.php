<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Cycle 19 audit: the "recurring" schedule was a ONE-SHOT RUN.
 *
 * Every spec producer (BackupScheduleSpec, AppCallSpec) emits
 * `{interval_seconds, activity, …}`. The publisher read `workflow`/`args`/
 * `task_queue` and called startWorkflow ONCE per entry, so `interval_seconds`
 * was silently DISCARDED — the cadence the whole feature exists for never
 * reached anything. And the in-code claim that "the client is injected and
 * built lazily by the publisher itself" was false: the publisher never builds
 * one, so `temporal` had exactly one reachable outcome, the no-client throw.
 *
 * RecurringScheduleClient is the additive half that makes a cadence
 * expressible without shipping temporal/sdk.
 */
final class RecurringSchedulePublisherTest extends TestCase
{
    /** @var list<array{scheduleId: string, activity: string, interval: int, queue: string, args: array<string, mixed>}> */
    public array $armed = [];

    public function test_it_satisfies_the_seam(): void
    {
        self::assertInstanceOf(SchedulePublisher::class, $this->recurringPublisher());
    }

    public function test_interval_seconds_arms_a_RECURRING_schedule_not_a_single_run(): void
    {
        $status = $this->recurringPublisher()->install(
            (new BackupScheduleSpec(86400))->installSpec(),
        );

        // The dump cadence must reach the client as a RECURRING schedule.
        self::assertCount(2, $this->armed, 'both spec entries must arm');
        self::assertSame('backup-dump', $this->armed[0]['scheduleId']);
        self::assertSame(86400, $this->armed[0]['interval'], 'the daily cadence is the point of the feature');
        self::assertSame('teleframe:tstore backup', $this->armed[0]['activity']);

        self::assertStringContainsString('recurring every 86400s', (string) $status['backup-dump']);
        self::assertStringNotContainsString('ONCE', (string) $status['backup-dump']);

        // The watchdog cadence is honoured from the spec too, not defaulted away.
        self::assertSame(3600, $this->armed[1]['interval']);
    }

    public function test_arming_is_idempotent_by_schedule_id(): void
    {
        $publisher = $this->recurringPublisher();

        $first = $publisher->install((new BackupScheduleSpec(3600))->installSpec());
        $second = $publisher->install((new BackupScheduleSpec(3600))->installSpec());

        // Same ids, so a boot-time install refreshes rather than stacks a
        // second backup per deploy.
        self::assertSame(array_keys($first), array_keys($second));
        self::assertSame('backup-dump', $this->armed[0]['scheduleId']);
    }

    public function test_a_one_shot_client_says_LOUDLY_that_nothing_recurs(): void
    {
        // The old surface, still supported: TemporalScheduleClient alone. It
        // starts a single run, so the status must not imply a cadence.
        $publisher = new TemporalSchedulePublisher(
            '127.0.0.1:7233',
            new class implements TemporalScheduleClient
            {
                public function startWorkflow(string $workflow, string $taskQueue, array $args): string
                {
                    return 'wf-once';
                }
            },
        );

        $status = $publisher->install((new BackupScheduleSpec(86400))->installSpec());

        self::assertStringContainsString('not recurring', (string) $status['backup-dump']);
        self::assertStringContainsString('86400', (string) $status['backup-dump']);
        self::assertFalse($publisher->armsRecurring());
    }

    public function test_the_no_client_refusal_does_not_claim_the_sdk_is_a_dependency(): void
    {
        // The old message asserted "temporal/sdk is a dependency" — it is
        // require-DEV only, and the package keeps its runtime surface tiny.
        try {
            (new TemporalSchedulePublisher('127.0.0.1:7233'))->install(
                (new BackupScheduleSpec(86400))->installSpec(),
            );
            self::fail('expected a loud refusal');
        } catch (RuntimeException $e) {
            self::assertStringNotContainsString('sdk is a dependency', $e->getMessage());
            self::assertStringContainsString(TemporalScheduleClient::class, $e->getMessage());
            self::assertStringContainsString(RecurringScheduleClient::class, $e->getMessage());
            self::assertStringContainsString('require-dev', $e->getMessage());
        }
    }

    public function test_constructing_never_dials_and_never_builds_a_client(): void
    {
        // The smoke gate constructs every module with no container booted.
        self::assertInstanceOf(
            TemporalSchedulePublisher::class,
            new TemporalSchedulePublisher('127.0.0.1:7233'),
        );
    }

    public function test_describe_warns_when_the_client_cannot_recur(): void
    {
        $publisher = new TemporalSchedulePublisher(
            '127.0.0.1:7233',
            new class implements TemporalScheduleClient
            {
                public function startWorkflow(string $workflow, string $taskQueue, array $args): string
                {
                    return 'wf-once';
                }
            },
        );

        self::assertStringContainsString('not on a cadence', $publisher->describe());
    }

    private function recurringPublisher(): TemporalSchedulePublisher
    {
        $armed = &$this->armed;
        $client = new class($armed) implements RecurringScheduleClient
        {
            /** @param list<array{scheduleId: string, activity: string, interval: int, queue: string, args: array<string, mixed>}> $sink */
            public function __construct(private array &$sink) {}

            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                $this->sink[] = [
                    'scheduleId' => $scheduleId,
                    'activity' => $activity,
                    'interval' => $intervalSeconds,
                    'queue' => $taskQueue,
                    'args' => $args,
                ];

                return 'sched-'.$scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-unused';
            }
        };

        return new TemporalSchedulePublisher('127.0.0.1:7233', $client, 'default');
    }
}
