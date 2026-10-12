<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Notify;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\Queue as QueueConnection;
use MeRezaRezaei\Teleframe\Notify\AmqpNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/**
 * LaravelQueueNotificationQueue — the async notify driver over the HOST'S OWN
 * Laravel queue. This is what makes the shipped default actually queue-shaped
 * without requiring a broker: illuminate/queue is a hard dependency of
 * laravel/framework, and the `database` driver needs a table rather than a
 * daemon, so a fresh install that has `QUEUE_CONNECTION=database` (what the
 * Laravel installer writes) gets durable async notifications out of the box.
 * A host with redis/sqs/beanstalkd gets that instead — the package never
 * picks infrastructure the host did not choose; it only refuses to be inline
 * by default.
 *
 * Same contract, same retry/DL accounting as AmqpNotificationQueue (design
 * §5, copied from that driver's docblock so the two read alike):
 *   - durable queue name QUEUE (tframe.notify — an opaque wire key, never
 *     renamed), dead-letter queue DEAD_LETTER (tframe.notify.dl, the key
 *     SyncNotificationQueue::DEAD_LETTER also answers);
 *   - the handler owns failures (logs + rethrows); the driver keeps the
 *     accounting: a job under the cap is re-published with attempts+1, a job
 *     at the cap is parked on the dead-letter queue, and the envelope is
 *     deleted either way (in `finally`) so nothing is delivered twice and a
 *     crashed drain loses nothing — repost/DL happens before the delete;
 *   - a malformed envelope (not ours, truncated, hand-edited) has no job to
 *     account for: it is deleted and rethrown LOUDLY, never silently
 *     dropped (the logger must never swallow).
 *
 * THE HONEST TRADE-OF (a broker being down is the async posture's price):
 * enqueue() hands the payload to the host's queue backend and returns; if
 * that backend is unreachable AT ENQUEUE TIME the push throws out of
 * NotifyService::enqueue() and the notification is NOT stored anywhere — this
 * driver has no local write-ahead log. That is a deliberate trade: the caller
 * learns immediately (loud, in-process, same as the old inline failure) rather
 * than a log line disappearing into a void. A host that wants a buffered
 * margin instead of a loud throw sets the connection to a durable one
 * (database/redis) and keeps the throw as the backstop.
 *
 * Laravel-queue ownership split: a host's own `queue:work` runs
 * QueuedNotifyJob::handle() (Laravel owns retry/failed_jobs on that path);
 * `teleframe:notify work` calls consume() below, where this driver owns the
 * attempts cap and the dead-letter.
 */
final class LaravelQueueNotificationQueue implements NotificationQueueContract
{
    /** Laravel queue name the host's worker drains — opaque, never renamed. */
    public const QUEUE = 'tframe.notify';

    /** Dead-letter queue: a cap-exhausted job lands here for inspection. */
    public const DEAD_LETTER = 'tframe.notify.dl';

    /** Retry cap (design §5) — the same number the AMQP driver parks at. */
    public const RETRY_CAP = AmqpNotificationQueue::RETRY_CAP;

    /**
     * @param  LoggerInterface|null  $logger  dead-letter PARKING is logged at
     *                                        `error`, exactly as
     *                                        AmqpNotificationQueue does. The
     *                                        package default is a NullLogger,
     *                                        so without this a cap-exhausted
     *                                        job is invisible: the handler
     *                                        logged the FAILURE, the driver
     *                                        then caught it, parked it and
     *                                        deleted the envelope — and the
     *                                        operator saw nothing at all.
     */
    public function __construct(
        private readonly QueueFactory $queues,
        private readonly string $connection,
        private readonly string $queue = self::QUEUE,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Hand the job to the host's queue backend and return immediately — the
     * caller's request never performs the Telegram round-trip.
     */
    public function enqueue(NotificationJob $job): string
    {
        $this->connection()->pushOn($this->queue, new QueuedNotifyJob($job->toArray(), false, $this->connection));

        return $job->jobId;
    }

    /**
     * Drain up to $max jobs from the notify queue. Same two exit paths as the
     * AMQP driver: a handler that returns = consumed; a handler that rethrows
     * = re-published (attempts+1) or dead-lettered BEFORE the delete, then the
     * exception propagates to the caller (`teleframe:notify work` owns the
     * wait, BackfillWorker posture).
     */
    public function consume(callable $handler, int $max = 1): int
    {
        $handled = 0;
        $connection = $this->connection();

        while ($handled < $max) {
            $envelope = $connection->pop($this->queue);
            if ($envelope === null) {
                break; // empty queue
            }

            $job = null;
            try {
                $job = $this->hydrate($envelope->payload());
                $handler($job);
            } catch (Throwable $e) {
                if ($job instanceof NotificationJob) {
                    $this->republishOrDeadLetter($job);
                }
                throw $e;
            } finally {
                $envelope->delete();
            }

            $handled++;
        }

        return $handled;
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function deadLetterName(): string
    {
        return self::DEAD_LETTER;
    }

    /**
     * Retry bookkeeping, identical in effect to the AMQP driver: at the cap a
     * job is parked on the dead-letter queue, otherwise it is re-published one
     * attempt further. Both happen before the envelope is deleted.
     */
    private function republishOrDeadLetter(NotificationJob $job): void
    {
        if ($job->attempts >= self::RETRY_CAP) {
            // The parking itself, not just the failure that caused it: the
            // handler's error log is the only other trace, and with the
            // package's NullLogger default there is no trace at all. Message
            // and level are byte-identical to AmqpNotificationQueue so the two
            // drivers read alike in one log stream.
            $this->logger?->error('teleframe.notify: job parked on the dead-letter queue', [
                'dead_letter' => self::DEAD_LETTER,
                'job_id' => $job->jobId,
                'topic' => $job->topic,
                'attempts' => $job->attempts,
                'retry_cap' => self::RETRY_CAP,
            ]);

            $this->connection()->pushOn(
                self::DEAD_LETTER,
                new QueuedNotifyJob($job->toArray(), true, $this->connection)
            );

            return;
        }

        $this->connection()->pushOn($this->queue, new QueuedNotifyJob(
            $this->withAttempt($job, $job->attempts + 1)->toArray(),
            false,
            $this->connection
        ));
    }

    /**
     * Pull our payload back out of a delivered envelope. Laravel serializes
     * the job OBJECT into `data.command` (a serialized string, or the object
     * itself when the connection encrypts payloads), so the read is
     * unserialize-then-extract; the class allow-list keeps a hand-edited
     * queue row from instantiating arbitrary classes.
     *
     * @param  array<string, mixed>  $payload
     */
    private function hydrate(array $payload): NotificationJob
    {
        $command = $payload['data']['command'] ?? null;

        if (is_string($command)) {
            try {
                $command = unserialize($command, ['allowed_classes' => [QueuedNotifyJob::class]]);
            } catch (Throwable $e) {
                throw new RuntimeException('malformed notify envelope: unserializable payload — '.$e->getMessage(), 0, $e);
            }
        }

        if (! $command instanceof QueuedNotifyJob) {
            throw new UnexpectedValueException('malformed notify envelope: expected '.QueuedNotifyJob::class.', got '.get_debug_type($command));
        }

        return NotificationJob::fromArray($command->payload);
    }

    /**
     * The same job, one attempt further — attempts is driver-owned on the wire
     * (mirrors AmqpNotificationQueue::retry()).
     */
    private function withAttempt(NotificationJob $job, int $attempts): NotificationJob
    {
        return new NotificationJob(
            $job->jobId,
            $job->topic,
            $job->level,
            $job->brief,
            $job->context,
            $job->attachment,
            $job->target,
            $job->premium,
            $attempts,
            $job->createdAt,
        );
    }

    private function connection(): QueueConnection
    {
        return $this->queues->connection($this->connection);
    }
}
