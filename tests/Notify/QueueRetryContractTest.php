<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Laravel\Notify\LaravelQueueNotificationQueue;
use MeRezaRezaei\Teleframe\Laravel\Notify\QueuedNotifyJob;
use MeRezaRezaei\Teleframe\Notify\AmqpNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;

/**
 * The retry contract on the PRIMARY Laravel path.
 *
 * Two defects this pins, both of which the package's own docs denied:
 *
 *   G1 — the driver parked a cap-exhausted job on tframe.notify.dl and deleted
 *        the envelope with NO log line. The handler's error log is the only
 *        other trace, and the package default is a NullLogger, so the operator
 *        saw nothing. The AMQP driver was given an `error` log in f5a1db08;
 *        this asserts the two drivers are byte-identical here, so they cannot
 *        drift apart again.
 *
 *   G2 — QueuedNotifyJob declared no $tries, so on the host's own
 *        `queue:work` Laravel retried forever and failures landed in
 *        `failed_jobs` — NOT in the tframe.notify.dl key that
 *        NotificationQueueContract::deadLetterName() advertises as the escape
 *        hatch. The contract was a promise the primary driver did not keep.
 *
 * Everything here is hand-faked: no broker, no database, no worker, no sleep,
 * no loop. The container is only bound to 'queue' and restored in tearDown.
 */
final class QueueRetryContractTest extends TestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- G1 ----

    /**
     * The parking itself must be logged, not just the failure that caused it:
     * the driver catches the throw, so without this line the failure has no
     * trace at all under the package's NullLogger default.
     */
    public function test_a_dead_lettered_job_emits_an_error_level_log(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob(attempts: LaravelQueueNotificationQueue::RETRY_CAP));
        $logger = self::recordingLogger();
        $queue = new LaravelQueueNotificationQueue($manager, 'database', LaravelQueueNotificationQueue::QUEUE, $logger);

        try {
            $queue->consume(static function (): void {
                throw new RuntimeException('still down');
            });
        } catch (RuntimeException) {
            // expected: the failure still propagates to the drain.
        }

        self::assertCount(1, $manager->pushed(), 'the cap-exhausted job must reach the dead-letter queue');
        self::assertSame(LaravelQueueNotificationQueue::DEAD_LETTER, $manager->pushed()[0]['queue']);

        self::assertNotSame([], $logger->records, 'parking a job on the dead-letter is now an event with no log line');
        self::assertSame('error', $logger->records[0]['level'], 'a lost notification is an error, not a warning');
        self::assertSame(
            'teleframe.notify: job parked on the dead-letter queue',
            $logger->records[0]['message'],
        );
        self::assertSame(
            ['dead_letter' => 'tframe.notify.dl', 'job_id' => 'job-1', 'topic' => 'app.info', 'attempts' => 3, 'retry_cap' => 3],
            $logger->records[0]['context'],
        );
    }

    /**
     * A job UNDER the cap is re-published, not parked — so it must NOT produce
     * a dead-letter log. Otherwise the log cannot be trusted as a signal.
     */
    public function test_a_republished_job_is_not_logged_as_parked(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob(attempts: 0));
        $logger = self::recordingLogger();
        $queue = new LaravelQueueNotificationQueue($manager, 'database', LaravelQueueNotificationQueue::QUEUE, $logger);

        try {
            $queue->consume(static function (): void {
                throw new RuntimeException('down');
            });
        } catch (RuntimeException) {
            // expected.
        }

        self::assertSame(LaravelQueueNotificationQueue::QUEUE, $manager->pushed()[0]['queue']);
        self::assertSame([], $logger->records, 'a retried job is not a dead-letter event');
    }

    /**
     * The default logger is silence, and that must not be a TYPE error: the
     * driver is constructible with three arguments, as every existing call
     * site (and the plain-PHP-constructible smoke check) does.
     */
    public function test_the_logger_is_optional_so_construction_stays_three_argument(): void
    {
        $queue = new LaravelQueueNotificationQueue(new FakeQueueManager, 'database');

        self::assertInstanceOf(LaravelQueueNotificationQueue::class, $queue);
    }

    // ---------------------------------------------------------------- G2 ----

    /**
     * The number is the DRIVER's, not a new constant: if the two paths ever
     * disagree about how many attempts a notify gets, this fails.
     */
    public function test_the_queued_job_declares_the_drivers_own_retry_cap(): void
    {
        $job = new QueuedNotifyJob(self::sampleJob()->toArray());

        self::assertSame(
            LaravelQueueNotificationQueue::RETRY_CAP,
            $job->tries,
            'the host `queue:work` path must give a job exactly as many attempts as the driver does',
        );
        self::assertSame(AmqpNotificationQueue::RETRY_CAP, $job->tries);
        self::assertSame(3, $job->tries);
    }

    public function test_the_queued_job_declares_a_timeout_and_a_backoff(): void
    {
        $job = new QueuedNotifyJob(self::sampleJob()->toArray());

        // A zero/absent timeout means "never killed", and a zero backoff is a
        // hot retry loop against a Telegram API that is already refusing.
        self::assertGreaterThan(0, $job->timeout, 'a notify send must not be able to hang a worker forever');
        self::assertGreaterThan(0, $job->backoff, 'retries must not be a hot loop');
    }

    /**
     * The half that actually makes the advertised dead-letter exist on this
     * path: without failed() the envelope goes to Laravel's `failed_jobs` and
     * tframe.notify.dl never receives anything.
     */
    public function test_an_exhausted_job_is_parked_on_the_advertised_dead_letter(): void
    {
        $manager = new FakeQueueManager;
        Container::setInstance($this->containerWithQueue($manager));

        $payload = self::sampleJob()->toArray();
        $job = new QueuedNotifyJob($payload, false, 'database');
        $job->failed(new RuntimeException('gave up'));

        self::assertCount(1, $manager->pushed(), 'failed() must not be a no-op');
        $pushed = $manager->pushed()[0];
        self::assertSame(LaravelQueueNotificationQueue::DEAD_LETTER, $pushed['queue']);
        self::assertSame($payload, $pushed['payload']->payload, 'the parked envelope keeps the original payload');
        self::assertTrue($pushed['payload']->deadLetter, 'a parked envelope must never re-send on a worker');
    }

    /**
     * Anti-divergence: every driver names ONE dead-letter key, and the Laravel
     * path parks on exactly that key. This is the assertion that fails if
     * somebody renames the wire key on one driver only.
     */
    public function test_the_laravel_path_parks_on_the_one_dead_letter_every_driver_answers(): void
    {
        $key = SyncNotificationQueue::DEAD_LETTER;
        self::assertSame($key, AmqpNotificationQueue::DEAD_LETTER);
        self::assertSame($key, LaravelQueueNotificationQueue::DEAD_LETTER);
        self::assertSame($key, (new LaravelQueueNotificationQueue(new FakeQueueManager, 'database'))->deadLetterName());
    }

    /**
     * The AMQP driver and the Laravel driver must log the SAME line on
     * parking: two drivers, one log stream, one message. If the AMQP side is
     * edited, this fails rather than letting the two drift.
     */
    public function test_both_drivers_say_the_same_thing_when_a_job_is_parked(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob(attempts: LaravelQueueNotificationQueue::RETRY_CAP));
        $laravel = self::recordingLogger();
        $queue = new LaravelQueueNotificationQueue($manager, 'database', LaravelQueueNotificationQueue::QUEUE, $laravel);

        try {
            $queue->consume(static function (): void {
                throw new RuntimeException('still down');
            });
        } catch (RuntimeException) {
            // expected.
        }

        $amqpAdapter = new FakeAmqpAdapter;
        $amqp = self::recordingLogger();
        $amqpDriver = new AmqpNotificationQueue($amqpAdapter, $amqp);
        $wire = self::sampleJob(attempts: AmqpNotificationQueue::RETRY_CAP);
        $amqpAdapter->publish((string) json_encode($wire->toArray(), JSON_THROW_ON_ERROR));

        try {
            $amqpDriver->consume(static function (): void {
                throw new RuntimeException('still down');
            });
        } catch (RuntimeException) {
            // expected.
        }

        self::assertSame(
            $amqp->records[0]['message'] ?? null,
            $laravel->records[0]['message'] ?? null,
            'the two drivers must log one message, not two that drift',
        );
        self::assertSame($amqp->records[0]['level'] ?? null, $laravel->records[0]['level'] ?? null);
        self::assertSame($amqp->records[0]['context'] ?? null, $laravel->records[0]['context'] ?? null);
    }

    // ------------------------------------------------------------- helpers --

    /** @return Container a container with ONLY 'queue' bound, as a worker has. */
    private function containerWithQueue(FakeQueueManager $manager): Container
    {
        $container = new Container;
        $container->instance('queue', $manager);
        Container::setInstance($container);

        return $container;
    }

    /** A PSR-3 logger that keeps every line, in order. */
    private static function recordingLogger(): AbstractLogger
    {
        return new class extends AbstractLogger
        {
            /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
    }

    private static function sampleJob(int $attempts = 0): NotificationJob
    {
        return new NotificationJob(
            jobId: 'job-1',
            topic: 'app.info',
            level: 'info',
            brief: 'first probe',
            context: ['src' => 'test'],
            attachment: null,
            target: ['chat' => '@ops', 'thread_id' => 2],
            premium: false,
            attempts: $attempts,
            createdAt: 1750000000,
        );
    }
}
