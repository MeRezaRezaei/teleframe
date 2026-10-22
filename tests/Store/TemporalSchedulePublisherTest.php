<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Task 3: "i prefer rabit mq to be used as queue and temporral for shcduls"
 * (verbatim 2026-09-16). The ledger filed this as "not a new goal" twice; an
 * index audit overturned it. The seam shipped, the config key shipped, and
 * temporal/sdk is already a dependency — only the publisher was missing, so
 * AppServiceProvider had to THROW for temporal.
 *
 * It must implement SchedulePublisher (install/describe), NOT a bespoke
 * publish()/dsn() surface — the binding resolves the interface.
 */
final class TemporalSchedulePublisherTest extends TestCase
{
    /** @var list<array{0: string, 1: string, 2: array<string, mixed>}> */
    public array $started = [];

    public function test_it_satisfies_the_seam(): void
    {
        self::assertInstanceOf(SchedulePublisher::class, $this->publisher());
    }

    public function test_install_starts_one_workflow_per_schedule(): void
    {
        $publisher = $this->publisher();

        $status = $publisher->install([
            'media-index' => ['workflow' => 'MediaIndexSweep', 'args' => ['bucket' => 'media']],
            'cache-prune' => ['workflow' => 'CachePrune', 'args' => []],
        ]);

        self::assertCount(2, $this->started);
        self::assertSame('MediaIndexSweep', $this->started[0][0]);
        self::assertSame(['bucket' => 'media'], $this->started[0][2]);
        self::assertCount(2, $status);
        self::assertStringContainsString('MediaIndexSweep', (string) $status['media-index']);
    }

    public function test_describe_names_the_driver_and_dsn(): void
    {
        $text = $this->publisher()->describe();

        self::assertStringContainsString('temporal', $text);
        self::assertStringContainsString('127.0.0.1:7233', $text);
    }

    public function test_constructing_never_dials_temporal(): void
    {
        // The client is injected and lazily built: a publisher that dialled
        // Temporal at construction would break the standalone smoke gate.
        self::assertInstanceOf(TemporalSchedulePublisher::class, new TemporalSchedulePublisher('127.0.0.1:7233'));
    }

    public function test_install_without_a_client_refuses_loudly_rather_than_dropping_jobs(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('refusing to silently drop');

        (new TemporalSchedulePublisher('127.0.0.1:7233'))->install([
            'media-index' => ['workflow' => 'MediaIndexSweep', 'args' => []],
        ]);
    }

    private function publisher(): TemporalSchedulePublisher
    {
        $started = &$this->started;
        $client = new class($started) implements TemporalScheduleClient
        {
            public function __construct(private array &$sink) {}

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                $this->sink[] = [$workflow, $taskQueue, $args];

                return 'wf-'.count($this->sink);
            }
        };

        return new TemporalSchedulePublisher('127.0.0.1:7233', $client);
    }
}
