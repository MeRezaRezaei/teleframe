<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * AmqpNotificationQueue (design doc §5, wave 2 — explicitly NOT slice 1,
 * spec §7): the async notification queue driver over an INJECTED broker
 * adapter — no hardcoded broker, so the class is plain-PHP constructible
 * with a fake adapter (round-trip tests run without ext-amqp). The real
 * broker surface belongs to the injected adapter: a host passes an object
 * exposing the duck-typed surface below, or a callable factory that
 * returns such an object lazily on first use (AmqpUploadQueue's lazy
 * connection posture — never dials at construction).
 *
 * AMQP conventions matched from src/Teleframe/Store/Queue/AmqpUploadQueue.php
 * (left untouched): durable queue <QUEUE> bound to the durable TOPIC
 * exchange <EXCHANGE> with routing key = queue name; declare + bind BEFORE
 * publishing (first-run safety — a message to a not-yet-bound queue would
 * be dropped); persistent delivery (delivery_mode 2); JSON bodies; manual
 * ack AFTER the handler ran (finally-ack — the wire must not redeliver
 * indefinitely). Dead-letter key <DEAD_LETTER> = tframe.notify.dl, the
 * canonical key SyncNotificationQueue::DEAD_LETTER answers (opaque, never
 * renamed — the tg:stream:dead-letter precedent).
 *
 * Retry accounting is driver-owned (design §5): "retry cap = 3 attempts
 * (attempts field); a job that still fails lands on the dead-letter queue
 * … never infinite redelivery". The handler owns failures — it logs and
 * rethrows (spec §7: the logger must never swallow). When a handler
 * rethrows, this driver re-publishes the job with attempts+1 up to the
 * cap; a job whose attempts are already at the cap is parked on the DL
 * queue for operator inspection. Either way the current envelope is acked
 * (finally), so the broker never redelivers the same envelope twice — a
 * crashed worker loses nothing because repost/DL happened before the ack.
 *
 * "Loud without ext-amqp" is delegated to the injected real adapter: an
 * ext-amqp adapter must throw at construction when ext-amqp is missing
 * (the AmqpUploadQueue::__construct loud posture) — this driver itself
 * stays broker-agnostic and never constructs a connection.
 *
 * INJECTED ADAPTER duck-type (the exact object surface this driver calls):
 *   publish(string $message): void
 *       — durable queue <QUEUE> already declared + bound to <EXCHANGE>;
 *         persistent delivery, content-type application/json.
 *   get(): ?array{body: string, delivery_tag: int|string}
 *       — the next envelope, or null when the queue is empty
 *         (mirrors AMQPQueue::get() returning false).
 *   ack(int|string $deliveryTag): void
 *       — acknowledge the delivered envelope (manual ack).
 *   publishDead(string $message): void
 *       — park a cap-exhausted job on the dead-letter queue <DEAD_LETTER>.
 */
final class AmqpNotificationQueue implements NotificationQueueContract
{
    /** Durable queue name (design §5 — opaque wire key, never renamed). */
    public const QUEUE = 'tframe.notify';

    /** Durable TOPIC exchange the queue binds to (AmqpUploadQueue convention). */
    public const EXCHANGE = 'teleframe.notify';

    /** Routing key = queue name (AmqpUploadQueue convention). */
    public const ROUTING_KEY = self::QUEUE;

    /** Dead-letter queue — the canonical key SyncNotificationQueue::DEAD_LETTER answers. */
    public const DEAD_LETTER = 'tframe.notify.dl';

    /** Retry cap: a job redelivered this many times parks on the DL (design §5). */
    public const RETRY_CAP = 3;

    private ?object $adapter = null;

    private ?Closure $factory = null;

    /**
     * @param  object|callable  $adapter  a queue-shaped adapter object (duck
     *                                    type above) or a callable factory
     *                                    returning one, resolved lazily
     * @param  LoggerInterface|null  $logger  dead-letter PARKING is logged at
     *                                        `error`. The package default is a
     *                                        NullLogger, so without this a
     *                                        cap-exhausted job is invisible:
     *                                        the handler logged the FAILURE,
     *                                        the driver then caught it, parked
     *                                        it and acked — and the operator saw
     *                                        nothing at all.
     */
    public function __construct(object|callable $adapter, private readonly ?LoggerInterface $logger = null)
    {
        if ($adapter instanceof Closure) {
            $this->factory = $adapter;

            return;
        }
        if (is_callable($adapter) && ! is_object($adapter)) {
            $this->factory = Closure::fromCallable($adapter);

            return;
        }
        $this->adapter = $adapter;
    }

    public function enqueue(NotificationJob $job): string
    {
        // Publish to the durable queue; the adapter declares + binds
        // before publishing (first-run safety, AmqpUploadQueue posture).
        $this->adapter()->publish($this->encode($job));

        return $job->jobId;
    }

    /**
     * Drain up to $max jobs. Two exit paths per envelope, always acked in
     * the finally block so the broker never redelivers: a handler that
     * returns = the job ran and the envelope is consumed; a handler that
     * rethrows = the driver re-published the job (attempts+1) or parked it
     * on the DL before the ack, then the exception propagates to the
     * worker (which owns the wait, BackfillWorker posture).
     */
    public function consume(callable $handler, int $max = 1): int
    {
        $handled = 0;
        while ($handled < $max) {
            $envelope = $this->adapter()->get();
            if (! is_array($envelope)) {
                break; // empty queue (null/false — AmqpUploadQueue posture)
            }
            $this->assertEnvelope($envelope);
            $job = null;
            try {
                $payload = json_decode((string) $envelope['body'], true, 512, JSON_THROW_ON_ERROR);
                $job = NotificationJob::fromArray(is_array($payload) ? $payload : []);
                $handler($job);
            } catch (Throwable $e) {
                // Handler owns failures and rethrows; the driver keeps the
                // retry accounting (repost attempts+1 up to the cap, then
                // DL). A malformed wire (hydration threw) has no job to
                // account for — acked and loud, never silently dropped.
                if ($job instanceof NotificationJob) {
                    $this->redeliverOrDeadLetter($job);
                }
                throw $e;
            } finally {
                $this->adapter()->ack($envelope['delivery_tag']);
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
     * Retry bookkeeping (design §5): a job at the retry cap lands on the
     * dead-letter queue; anything else is re-published to the main queue
     * with attempts+1. Called before the ack, so no job is ever lost
     * between the failure and the acknowledgement.
     */
    private function redeliverOrDeadLetter(NotificationJob $job): void
    {
        if ($job->attempts >= self::RETRY_CAP) {
            // The parking itself, not just the failure that caused it: the
            // handler's error log is the only other trace, and with the
            // package's NullLogger default there is no trace at all.
            $this->logger?->error('teleframe.notify: job parked on the dead-letter queue', [
                'dead_letter' => self::DEAD_LETTER,
                'job_id' => $job->jobId,
                'topic' => $job->topic,
                'attempts' => $job->attempts,
                'retry_cap' => self::RETRY_CAP,
            ]);

            $this->adapter()->publishDead($this->encode($job));

            return;
        }
        $this->adapter()->publish($this->encode($this->retry($job)));
    }

    /** The same job, one attempt further — attempts is driver-owned on the wire. */
    private function retry(NotificationJob $job): NotificationJob
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
            $job->attempts + 1,
            $job->createdAt,
        );
    }

    /** @return string the job payload as JSON (throw-on-error, upload posture) */
    private function encode(NotificationJob $job): string
    {
        return json_encode($job->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Loud on a malformed envelope: every delivery must carry a body and a
     * delivery tag — a broken adapter must surface, not silently vanish.
     *
     * @param  array<string, mixed>  $envelope
     */
    private function assertEnvelope(array $envelope): void
    {
        if (! isset($envelope['body'], $envelope['delivery_tag'])
            || ! is_string($envelope['body'])
            || (! is_int($envelope['delivery_tag']) && ! is_string($envelope['delivery_tag']))
        ) {
            throw new RuntimeException('amqp notification queue adapter returned a malformed envelope');
        }
    }

    /**
     * Resolve the injected adapter: a direct object is cached; a factory
     * callable runs once on first enqueue/consume (lazy-broker posture) —
     * construction itself never touches the broker.
     */
    private function adapter(): object
    {
        if ($this->adapter !== null) {
            return $this->adapter;
        }
        if ($this->factory === null) {
            throw new RuntimeException('amqp notification queue needs an adapter or an adapter factory');
        }
        $resolved = ($this->factory)();
        if (! is_object($resolved)) {
            throw new RuntimeException('amqp notification queue adapter factory must return an object');
        }
        $this->adapter = $resolved;

        return $resolved;
    }
}
