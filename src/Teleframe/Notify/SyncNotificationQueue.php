<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * SyncNotificationQueue (design doc §5): the inline default driver —
 * zero infra, correct ordering, works on a plain login. Same posture as
 * SyncUploadQueue and spec §6 Q6: sync is the default; choosing amqp is
 * choosing the broker, never the other way around. enqueue() hands the
 * job to the handler immediately, in the caller's process; consume() is
 * a no-op drain (an async worker asking a sync driver for jobs finds
 * none). The dead letter is nominal here — the sync handler owns
 * failures by rethrowing, so deadLetterName() answers the canonical DL
 * wire key (tframe.notify.dl) without ever parking a job.
 */
final class SyncNotificationQueue implements NotificationQueueContract
{
    public const DEAD_LETTER = 'tframe.notify.dl';

    /** @param  \Closure(NotificationJob): mixed|null  $handler */
    public function __construct(private readonly ?\Closure $handler = null) {}

    public function enqueue(NotificationJob $job): string
    {
        if ($this->handler !== null) {
            ($this->handler)($job);
        }

        return $job->jobId; // sync path: the job id IS the queue id
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0; // nothing pending in a sync driver
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function deadLetterName(): string
    {
        return self::DEAD_LETTER;
    }
}
