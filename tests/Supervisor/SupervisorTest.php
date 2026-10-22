<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Supervisor;

use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessHandle;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Supervisor\ProcessState;
use MeRezaRezaei\Teleframe\Supervisor\Supervisor;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use MeRezaRezaei\Teleframe\Supervisor\UnknownProcessException;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use MeRezaRezaei\Teleframe\Tests\Support\FakeProcessSpawner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The package-owned process supervisor, proven with plain-PHP fakes and an
 * injected clock: a crashed process is restarted after its backoff window, an
 * unknown process name refuses loudly, and the whole thing constructs with no
 * container booted and no process spawned.
 */
final class SupervisorTest extends TestCase
{
    /** The whole test doubles the clock; no real sleeping anywhere. */
    private int $now = 1_700_000_000;

    public function test_a_crashed_process_is_restarted_after_its_backoff_window(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);

        $supervisor->adopt($this->spec());
        self::assertSame(1, $spawner->spawns);
        self::assertSame(ProcessState::RUNNING, $supervisor->state('ingest')?->status);

        // The process dies. Same tick: no restart yet — it is in backoff.
        $spawner->crash('ingest', exitCode: 137);
        $supervisor->tick();

        $row = $supervisor->state('ingest');
        self::assertNotNull($row);
        self::assertSame(ProcessState::BACKOFF, $row->status);
        self::assertSame(137, $row->lastExitCode);
        self::assertSame(1, $row->restarts);
        self::assertSame(5, $row->backoffSeconds);
        self::assertSame(1, $spawner->spawns, 'a restart must wait out the backoff window');

        // Still inside the window one tick later.
        $this->now += 4;
        $supervisor->tick();
        self::assertSame(1, $spawner->spawns);

        // Window elapsed: the process is back, and the backoff resets.
        $this->now += 2;
        $supervisor->tick();

        self::assertSame(2, $spawner->spawns);
        $row = $supervisor->state('ingest');
        self::assertSame(ProcessState::RUNNING, $row?->status);
        self::assertNotNull($row?->pid);
        self::assertSame(0, $row->backoffSeconds);
        self::assertSame(137, $row->lastExitCode, 'the last exit code is kept for the ops view');
    }

    public function test_reports_are_json_ready_rows(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);
        $supervisor->adopt($this->spec());

        $row = $supervisor->report()[0]->toArray();
        self::assertSame(
            ['name', 'status', 'pid', 'restarts', 'last_exit_code', 'backoff_seconds', 'next_attempt_at'],
            array_keys($row),
        );
        self::assertIsString(json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_repeated_crashes_walk_the_exponential_ladder_and_can_be_exhausted(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);

        $supervisor->adopt(new ProcessSpec(
            name: 'ingest',
            command: ['php', 'artisan', 'teleframe:ingest'],
            restartDelaySeconds: 2,
            maxBackoffSeconds: 8,
            maxRestarts: 3,
        ));

        $delays = [];
        for ($i = 0; $i < 4; $i++) {
            $spawner->crash('ingest');
            $supervisor->tick();
            $row = $supervisor->state('ingest');
            $delays[] = $row?->backoffSeconds ?? 0;
            $this->now += 100;
            $supervisor->tick(); // restart when due
        }

        self::assertSame([2, 4, 8, 8], $delays, 'backoff doubles and then holds at the ceiling');
        self::assertSame(ProcessState::EXHAUSTED, $supervisor->state('ingest')?->status);

        $before = $spawner->howManyTimesSpawned('ingest');
        $this->now += 1000;
        $supervisor->tick();
        self::assertSame($before, $spawner->howManyTimesSpawned('ingest'), 'an exhausted process is not respawned');
    }

    public function test_a_process_that_cannot_be_spawned_still_gets_the_backoff_ladder(): void
    {
        $spawner = new FakeProcessSpawner;
        $spawner->failSpawnFor = 'relay';
        $log = new ArrayLogger;

        $supervisor = new Supervisor(
            $spawner,
            clock: fn (): int => $this->now,
            sleeper: static function (int $seconds): void {},
            logger: $log,
        );

        $supervisor->adopt(new ProcessSpec(name: 'relay', command: ['php', 'artisan', 'teleframe:relay']));
        $supervisor->tick();

        $row = $supervisor->state('relay');
        self::assertSame(0, $spawner->spawns);
        self::assertNotNull($row);
        self::assertSame(ProcessState::BACKOFF, $row->status);
        $errors = array_values(array_filter($log->records, static fn (array $r): bool => $r['level'] === 'error'));
        self::assertNotSame([], $errors, 'an unstartable process is logged, never swallowed');
    }

    public function test_stop_kills_every_supervised_process(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);

        $supervisor->adopt($this->spec());
        $supervisor->adopt(new ProcessSpec(name: 'daemon', command: ['php', 'artisan', 'teleframe:daemon']));
        self::assertSame(0, $spawner->kills, 'adopting never kills anything');

        $supervisor->stop();
        self::assertSame(2, $spawner->kills);
        self::assertSame(ProcessState::STOPPED, $supervisor->state('ingest')?->status);
    }

    public function test_supervise_loop_bounds_ticks_and_drives_crashes_to_restarts(): void
    {
        $spawner = new FakeProcessSpawner;
        $slept = [];
        $supervisor = new Supervisor(
            $spawner,
            clock: function (): int {
                $this->now += 6; // every tick advances past the backoff window

                return $this->now;
            },
            sleeper: static function (int $seconds) use (&$slept): void {
                $slept[] = $seconds;
            },
        );

        $supervisor->adopt($this->spec());
        $spawner->crash('ingest');

        $rows = $supervisor->supervise([], tickSeconds: 3, maxTicks: 3);

        self::assertSame([3, 3], $slept);
        self::assertSame(2, $spawner->spawns, 'the crashed process was restarted inside the bounded loop');
        self::assertSame(ProcessState::RUNNING, $rows[0]->status);
    }

    public function test_an_unknown_process_name_refuses_loudly_and_names_the_shipped_ones(): void
    {
        $catalog = new ProcessCatalog;

        $this->expectException(UnknownProcessException::class);
        $this->expectExceptionMessage('Unknown supervised process "ingst"');
        $this->expectExceptionMessage('daemon');

        $catalog->specFor('ingst');
    }

    public function test_an_unknown_name_in_a_batch_aborts_the_whole_supervision_set(): void
    {
        $catalog = new ProcessCatalog;

        try {
            $catalog->specsFor(['daemon', 'nope']);
            self::fail('a partially-supervised set must never be produced');
        } catch (UnknownProcessException $e) {
            self::assertStringContainsString('nope', $e->getMessage());
        }
    }

    public function test_the_catalog_covers_the_shipped_long_running_commands(): void
    {
        $catalog = new ProcessCatalog;

        self::assertSame(['daemon', 'ingest', 'relay', 'tstore-work'], $catalog->names());
        self::assertSame(['teleframe:daemon'], $catalog->commandFor('daemon'));
        self::assertSame(['teleframe:tstore', 'work'], $catalog->commandFor('tstore-work'));

        $spec = $catalog->specFor('ingest', phpBinary: '/usr/bin/php8.3', artisanPath: '/app/artisan');
        self::assertSame(['/usr/bin/php8.3', '/app/artisan', 'teleframe:ingest'], $spec->command);
    }

    public function test_the_supervisor_constructs_with_no_container_and_spawns_nothing(): void
    {
        // No container, no logger, no clock: the default seams are inert.
        $supervisor = new Supervisor(new FakeProcessSpawner);

        self::assertSame([], $supervisor->report());
        self::assertNull($supervisor->state('ingest'));
    }

    public function test_the_real_spawner_constructs_without_opening_a_process(): void
    {
        $spawner = new SystemProcessSpawner;
        $handle = new ProcessHandle(id: 99, name: 'ghost', pid: 0);

        self::assertFalse($spawner->isAlive($handle), 'a handle the spawner never issued is simply not alive');
        self::assertNull($spawner->exitCode($handle));
        $spawner->kill($handle); // killing an unknown handle is a no-op, not an error
    }

    public function test_logger_defaults_to_silent(): void
    {
        $reflection = new \ReflectionProperty(Supervisor::class, 'logger');

        self::assertInstanceOf(LoggerInterface::class, $reflection->getValue(new Supervisor(new FakeProcessSpawner)));
    }

    public function test_a_dropped_supervisor_kills_its_children_instead_of_orphaning_them(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);

        $supervisor->adopt($this->spec());
        self::assertSame(1, $spawner->spawns, 'precondition: the child is running');
        self::assertSame(0, $spawner->kills, 'precondition: nothing is killed while running');

        // No stop(), no signal — just let the object go out of scope, which is
        // exactly what an uncaught throw or a fatal error looks like from here.
        unset($supervisor);

        self::assertSame(
            1,
            $spawner->kills,
            'a supervisor that goes out of scope must terminate its children, not leak them',
        );
    }

    public function test_reset_kills_before_forgetting_so_it_cannot_orphan_by_design(): void
    {
        $spawner = new FakeProcessSpawner;
        $supervisor = $this->supervisor($spawner);

        $supervisor->adopt($this->spec());
        $supervisor->reset();

        self::assertSame(1, $spawner->kills, 'reset() must reap, not merely forget');
        self::assertSame([], $supervisor->report());
    }

    public function test_the_real_spawner_terminates_an_actual_child_process_on_destruction(): void
    {
        $spawner = new SystemProcessSpawner;

        $handle = $spawner->spawn(new ProcessSpec(
            name: 'sleeper',
            command: [PHP_BINARY, '-r', 'sleep(30);'],
        ));

        $pid = $handle->pid;
        self::assertGreaterThan(0, $pid);
        self::assertTrue($spawner->isAlive($handle), 'precondition: the child is really running');
        self::assertTrue($this->processIsAlive($pid));

        // Drop the only reference to the spawner. If __destruct() did not reap,
        // a real `sleep 30` would survive this line and linger for 30s.
        unset($spawner);

        self::assertFalse(
            $this->processIsAlive($pid),
            'the real spawner must terminate its children when it is torn down',
        );
    }

    private function processIsAlive(int $pid): bool
    {
        if (! function_exists('posix_kill')) {
            self::markTestSkipped('ext-posix is required to observe a real child process.');
        }

        return @posix_kill($pid, 0);
    }

    private function supervisor(FakeProcessSpawner $spawner): Supervisor
    {
        return new Supervisor(
            $spawner,
            clock: fn (): int => $this->now,
            sleeper: static function (int $seconds): void {},
        );
    }

    private function spec(): ProcessSpec
    {
        return new ProcessSpec(
            name: 'ingest',
            command: ['php', 'artisan', 'teleframe:ingest'],
            restartDelaySeconds: 5,
            maxBackoffSeconds: 60,
        );
    }
}
