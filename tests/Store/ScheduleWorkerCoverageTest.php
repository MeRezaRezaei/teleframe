<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * THE HONESTY-PROPERTY TEST. This is the test that would have caught the
 * defect this slice fixes.
 *
 * `SdkTemporalScheduleClient` arms a real server-side Temporal schedule whose
 * action is `StartWorkflowAction::new($type)` — the workflow type is a RAW
 * STRING. Before `TemporalWorker` shipped, nothing in the tree implemented any
 * of those strings: `grep -rln "WorkerFactory|registerWorkflowTypes|
 *
 * @WorkflowInterface|@ActivityInterface" src/` returned ZERO files. So a host
 * that set `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` and installed
 * `temporal/sdk` read `armed %s recurring every %ds` for a schedule whose
 * every tick started a workflow type nothing implemented.
 *
 * That is the same shape as the false greens this cycle has been removing
 * everywhere else — a class that exists, a config key that is read, a test that
 * constructs the object, and a capability that is unreachable. Before commit
 * 729452d1 the driver could only THROW, loudly, by name; replacing that with a
 * silent green made it worse.
 *
 * So the pin is exactly this: arm a schedule for a workflow type the package
 * does NOT serve, and the status MUST say so. An uncovered type reported as a
 * green `armed` line is the defect, in one assertion.
 */
final class ScheduleWorkerCoverageTest extends TestCase
{
    #[Test]
    public function arming_a_type_the_package_does_not_serve_says_so_loudly(): void
    {
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        $status = $publisher->install([
            // A third party's workflow type. This package implements none, and
            // the string is deliberately NOT one of the shipped families.
            'third-party-report' => [
                'activity' => 'acme.weekly-report',
                'interval_seconds' => 604800,
            ],
        ]);

        $line = $status['third-party-report'];

        self::assertStringContainsString('armed acme.weekly-report', $line);
        self::assertStringContainsString('NOT SERVED by any worker in this package', $line,
            'an armed schedule for an unserved workflow type must state that no worker in this package serves it');
        self::assertStringContainsString('implements no workflow type acme.weekly-report', $line,
            'the verdict must name the exact type, so the operator can see which job is dead');
        self::assertStringContainsString('every tick of this schedule will fail', $line,
            'the failure mode must be stated, not left for the operator to discover');
        self::assertStringContainsString('Serve it from your own worker, or do not arm it', $line,
            'the text must name the two real options, not just report the problem');
    }

    #[Test]
    public function describe_reports_the_uncovered_type_after_install(): void
    {
        // The same truth through the OTHER surface an operator reads. A status
        // line that scrolls past is not disclosure; describe() is what
        // `teleframe:tstore schedules` (no --install) prints, and it is the
        // text someone reads when a job is not firing.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        $publisher->install([
            'third-party-report' => ['activity' => 'acme.weekly-report', 'interval_seconds' => 604800],
        ]);

        $text = $publisher->describe();

        self::assertStringContainsString('acme.weekly-report', $text);
        self::assertStringContainsString('NOT SERVED by any worker in this package', $text);
    }

    #[Test]
    public function a_served_type_is_reported_as_served_and_not_as_not_served(): void
    {
        // The other direction, because a disclosure that says NOT SERVED for
        // everything is just as useless as one that says nothing. This is the
        // test that stops "fixing" the defect by making every status alarming.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        $status = $publisher->install([
            'backup-dump' => ['activity' => 'teleframe:tstore backup', 'interval_seconds' => 900],
        ]);

        self::assertStringContainsString('worker: served by this package', $status['backup-dump']);
        self::assertStringNotContainsString('NOT SERVED', $status['backup-dump']);
    }

    #[Test]
    public function the_one_shot_path_also_discloses_worker_coverage(): void
    {
        // A one-shot startWorkflow() names the same workflow type, so it has
        // the same uncovered-type problem. Disclosing only on the recurring
        // branch would leave a third-party host with the false green intact on
        // the other path.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->oneShotClient());

        $status = $publisher->install([
            'third-party-report' => ['activity' => 'acme.weekly-report', 'interval_seconds' => 60],
        ]);

        self::assertStringContainsString('NOT SERVED by any worker in this package', $status['third-party-report']);
    }

    #[Test]
    public function describe_before_install_still_says_nothing_is_armed(): void
    {
        // The footer I added must not resurrect the old unconditional claim.
        // A driver alone arms nothing, and the text must keep saying so.
        $text = (new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient()))->describe();

        self::assertStringContainsString('Nothing is armed', $text);
        self::assertStringContainsString('install() has not run', $text);
        self::assertStringNotContainsString('armed by THIS process', $text);
    }

    #[Test]
    public function the_standing_worker_sentence_names_the_entry_point_and_the_queue(): void
    {
        // The per-type verdict only speaks for types THIS process armed —
        // describe() dials nothing and cannot enumerate a server. So the
        // standing half has to tell the operator how to find out for
        // themselves, on the right queue: a worker on the wrong queue never
        // sees a tick, and that is as silent as no worker at all.
        $text = (new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient(), 'tstore'))->describe();

        self::assertStringContainsString('php bin/teleframe-worker', $text);
        self::assertStringContainsString('`tstore`', $text, 'the worker must be started on the queue the schedules were armed on');
        self::assertStringContainsString('SCHEDULE side', $text);
        self::assertStringContainsString('No live Temporal server has been exercised', $text,
            'arming a schedule must never read as proof that it fires');
    }

    /**
     * @return RecurringScheduleClient
     */
    private function recurringClient(): object
    {
        return new class implements RecurringScheduleClient
        {
            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                return $scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-not-used';
            }
        };
    }

    private function oneShotClient(): object
    {
        return new class implements TemporalScheduleClient
        {
            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-1';
            }
        };
    }
}
