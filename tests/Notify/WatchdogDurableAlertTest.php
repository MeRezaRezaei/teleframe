<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Notify\FileWatchAlertSink;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\WatchAlert;
use MeRezaRezaei\Teleframe\Notify\WatchAlertSinkContract;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE GUARD ON THE OUT-OF-BAND ALERT (F8, S6, 2026-10-13).
 *
 * THE DEFECT IT PINS. `NoShowWatchdog::emit()` raised a finding by enqueueing on
 * `{topic}.missing` — through the same queue and transport as the heartbeat it
 * watches. So when a queue or transport failure was WHY the heartbeat went
 * missing, which is the operator's own scenario ("if something did not come
 * into a log channel as a result of failures"), the alert died with its
 * subject, and all that survived was a PSR-3 line nobody outside the host can
 * see. A watchdog whose alert rides the failing channel is a comment, not a
 * watchdog.
 *
 * WHY THIS FILE PROVES ORDER, NOT PRESENCE. Asserting "a file exists after a
 * check()" is satisfied by any implementation that writes the file somewhere in
 * emit() — including one that writes it AFTER the notification, which is the
 * defect restated. So GATE 1 watches the log FROM INSIDE the notify queue: the
 * queue reads the log at the moment it is handed the alert, and the assertion
 * is that the finding was already there. Only an implementation that records
 * first can pass it.
 */
final class WatchdogDurableAlertTest extends TestCase
{
    /** The overdue heartbeat every fixture below is built around. */
    private const LAST_SEEN = 10_000;

    private const EXPECT_EVERY = 100;

    private const NOW = 10_500;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir().'/teleframe-watchdog-test-'.getmypid().'-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        $this->logPath = $dir.'/alerts.jsonl';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
        if (is_dir(dirname($this->logPath))) {
            rmdir(dirname($this->logPath));
        }

        parent::tearDown();
    }

    /**
     * GATE 1 — THE DURABLE RECORD IS WRITTEN BEFORE THE NOTIFICATION IS
     * ATTEMPTED. The queue peeks at the log inside enqueue(); pre-fix there was
     * no log to peek at, and any implementation that recorded the finding after
     * enqueue() would fail this.
     */
    public function test_the_durable_record_is_written_before_the_notification_is_attempted(): void
    {
        $linesAtEnqueue = null;
        $queue = new PeekingNotifyQueue(function () use (&$linesAtEnqueue): void {
            $linesAtEnqueue = $this->recordedLines();
        });

        $alerts = $this->watchdog($queue)->check();

        self::assertCount(1, $alerts, 'the fixture must produce exactly one no-show finding');
        self::assertSame(
            ['backup.missing'],
            $queue->enqueuedTopics(),
            'the Telegram path must still fire — it is an ENHANCEMENT on top of the record, not a'
            ."\nreplacement for it. Silently dropping it would trade one lost alert for another.",
        );

        self::assertNotSame(
            [],
            $linesAtEnqueue ?? [],
            'the alert log was EMPTY at the moment the notification was handed to the queue. emit()'
            ."\ntried to notify before it recorded the finding, so a queue failure would still take"
            ."\nthe evidence with it. The record must be written FIRST and unconditionally.",
        );
    }

    /**
     * GATE 2 — THE RECORD SURVIVES A QUEUE THAT IS EXACTLY WHY THE HEARTBEAT
     * WENT MISSING. The operator's scenario in full: the transport is broken, so
     * the notification throws — and the finding is on disk anyway.
     *
     * The throw is still expected, and still asserted: the loudness contract
     * ("the caller owns recovery, exactly like NotifySender") is not changed by
     * this fix. What changed is that the throw no longer costs the evidence.
     */
    public function test_the_record_survives_a_queue_failure_that_caused_the_silence(): void
    {
        $queue = new ThrowingNotifyQueue('the queue is down — this is why the heartbeat is missing');

        try {
            $this->watchdog($queue)->check();
            self::fail('a broken notify queue must still throw: the caller owns recovery.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the queue is down', $e->getMessage());
        }

        $lines = $this->recordedLines();

        self::assertCount(
            1,
            $lines,
            'the no-show finding must be on disk DESPITE the notification throwing. Pre-fix there was'
            ."\nno record at all: the alert rode the failing queue and was lost with the heartbeat it"
            ."\nwas reporting.",
        );

        self::assertSame('backup', $lines[0]['topic'], 'the record must name the topic that went missing');
        self::assertSame(
            $lines[0]['age_seconds'],
            $lines[0]['context']['age_seconds'],
            'the record carries a fixed top-level schema AND the caller\'s context verbatim, so a'
            ."\nreader of the log never has to know what the caller named its fields today.",
        );
    }

    /**
     * GATE 3 — A BROKEN SINK DOES NOT SUPPRESS THE NOTIFICATION. The mirror
     * image of GATE 2, and the half that is easy to get wrong in the other
     * direction: rethrowing a sink failure would make a full disk the reason no
     * alert is sent at all, which is strictly worse than what this fix prevents.
     */
    public function test_a_broken_sink_does_not_suppress_the_notification(): void
    {
        $queue = new PeekingNotifyQueue;

        $alerts = $this->watchdog($queue, new ExplodingWatchAlertSink)->check();

        self::assertCount(1, $alerts, 'the fixture must produce exactly one no-show finding');
        self::assertSame(
            ['backup.missing'],
            $queue->enqueuedTopics(),
            'a sink that throws must not stop the notification. The durable path is the GUARANTEE that'
            ."\nthe finding is recorded, not a gate on whether anyone is told.",
        );
    }

    /**
     * GATE 4 — THE FILE SINK IS APPEND-ONLY AND CREATES PRIVATELY. Two
     * findings, two lines, in order: a rewritten or truncated log is not
     * evidence. It also pins that the file is created 0600 rather than
     * inheriting the process umask, because this file lands in whatever
     * directory a host points the config key at.
     */
    public function test_the_file_sink_appends_and_creates_the_file_privately(): void
    {
        $sink = new FileWatchAlertSink($this->logPath, static fn (): int => self::NOW);

        self::assertFileDoesNotExist(
            $this->logPath,
            'constructing the sink must not create the file — a wrong path must fail at the first'
            ."\nrecord, not at wiring time (the spool_dir precedent: lazy, loud, never a silent no-op).",
        );

        $sink->record($this->alert(1), ['topic' => 'backup', 'age_seconds' => 1]);
        $sink->record($this->alert(2), ['topic' => 'backup', 'age_seconds' => 2]);

        $lines = $this->recordedLines();

        self::assertCount(2, $lines, 'two findings must be two lines, not one rewritten line');
        self::assertSame([1, 2], array_column($lines, 'age_seconds'), 'appended in call order');

        self::assertSame(
            '0600',
            substr(sprintf('%o', (int) fileperms($this->logPath)), -4),
            'the durable alert log must not be world-readable — it records what a host is failing at.',
        );
    }

    /**
     * GATE 5 — A BLANK PATH IS A WIRING ERROR, NOT A SILENT DISCARD. A sink
     * that cannot name its file would make every alert vanish while still
     * reporting success, which is the defect class this file exists to end.
     */
    public function test_a_blank_sink_path_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileWatchAlertSink('   ');
    }

    /**
     * GATE 6 — ZERO REGEX. `src/Teleframe/**` is a phpstan-disallowed
     * `preg_*` path, and the sink is new code in it. phpstan is the authority,
     * but the rule is stated here too so the file that introduces a parser into
     * this namespace is the file that says it may not.
     */
    public function test_the_sink_carries_no_regex(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/Teleframe/Notify/FileWatchAlertSink.php');

        self::assertIsString($source);
        self::assertStringNotContainsString(
            'preg_',
            (string) $source,
            'FileWatchAlertSink lives in src/Teleframe/**, where preg_* is a phpstan error.'
            ."\nParse with ctype_digit/str_* instead.",
        );
    }

    /**
     * A watchdog whose 'backup' heartbeat is overdue by 500 s against a 100 s
     * expectation, wired to $queue and to $sink.
     */
    private function watchdog(
        NotificationQueueContract $queue,
        ?WatchAlertSinkContract $sink = null,
    ): NoShowWatchdog {
        // A by-reference clock, so touch() can record LAST_SEEN and the check
        // still sees NOW — an injected clock that cannot move cannot express
        // "this heartbeat arrived earlier", which is the whole shape of a no-show.
        $clock = self::LAST_SEEN;
        $now = static function () use (&$clock): int {
            return $clock;
        };

        $watchdog = new NoShowWatchdog(
            state: new WatchState(new InMemoryCache, 'teleframe.notify.watch.'),
            notify: $queue,
            now: $now,
            logger: null,
            registrationSource: static fn (): array => ['backup' => self::EXPECT_EVERY],
            alertSink: $sink ?? new FileWatchAlertSink($this->logPath, static fn (): int => self::NOW),
        );

        $watchdog->touch('backup'); // last seen LAST_SEEN → age NOW - LAST_SEEN > EXPECT_EVERY

        $clock = self::NOW;

        return $watchdog;
    }

    private function alert(int $age): WatchAlert
    {
        return new WatchAlert('backup', self::EXPECT_EVERY, self::LAST_SEEN - $age, $age);
    }

    /**
     * The durable log, one decoded row per line.
     *
     * @return list<array<string, mixed>>
     */
    private function recordedLines(): array
    {
        if (! is_file($this->logPath)) {
            return [];
        }

        $raw = file_get_contents($this->logPath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $rows = [];
        foreach (explode("\n", trim($raw)) as $line) {
            $rows[] = json_decode($line, true, 8, JSON_THROW_ON_ERROR);
        }

        return $rows;
    }
}

/**
 * A queue that records what it was handed and, optionally, runs a probe at the
 * moment of the handoff — which is how GATE 1 observes the ORDER of the two
 * paths rather than just their presence.
 */
final class PeekingNotifyQueue implements NotificationQueueContract
{
    /** @var list<string> */
    public array $topics = [];

    public function __construct(private readonly ?\Closure $onEnqueue = null) {}

    public function enqueue(NotificationJob $job): string
    {
        $this->topics[] = $job->topic;

        if ($this->onEnqueue !== null) {
            ($this->onEnqueue)();
        }

        return $job->jobId;
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0;
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function deadLetterName(): string
    {
        return 'tframe.notify.dl';
    }

    /** @return list<string> */
    public function enqueuedTopics(): array
    {
        return $this->topics;
    }
}

/** The queue failure that is also the cause of the silence — GATE 2's subject. */
final class ThrowingNotifyQueue implements NotificationQueueContract
{
    public function __construct(private readonly string $message) {}

    public function enqueue(NotificationJob $job): string
    {
        throw new RuntimeException($this->message);
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0;
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function deadLetterName(): string
    {
        return 'tframe.notify.dl';
    }
}

/** A durable sink that cannot write — GATE 3's subject. */
final class ExplodingWatchAlertSink implements WatchAlertSinkContract
{
    public function record(WatchAlert $alert, array $context): void
    {
        throw new RuntimeException('the disk is full');
    }
}
