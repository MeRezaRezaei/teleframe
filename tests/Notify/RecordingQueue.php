<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;

/**
 * Capturing NotificationQueueContract: enqueue() records the job and returns
 * its id without running any handler — the tests inspect the wire payload the
 * service built without a transport, and assert on the job list afterwards.
 */
final class RecordingQueue implements NotificationQueueContract
{
    /** @var list<NotificationJob> jobs handed to enqueue(), in order */
    public array $jobs = [];

    public function enqueue(NotificationJob $job): string
    {
        $this->jobs[] = $job;

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
}
