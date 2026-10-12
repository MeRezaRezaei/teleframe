<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\Queue;

/**
 * FakeQueueManager — the Illuminate\Contracts\Queue\Factory the notify driver
 * takes, handing out ONE FakeNotifyQueue for any connection name. It exists so
 * the driver can be built and drained with no database, no redis, no broker
 * and no container — the standalone-smoke posture, in miniature.
 */
final class FakeQueueManager implements QueueFactory
{
    private FakeNotifyQueue $queue;

    public function __construct()
    {
        $this->queue = new FakeNotifyQueue;
    }

    public function connection($name = null): Queue
    {
        return $this->queue;
    }

    public function queue(): FakeNotifyQueue
    {
        return $this->queue;
    }

    /** @return list<array{queue: string, payload: object}> what the driver pushed */
    public function pushed(): array
    {
        return $this->queue->pushed;
    }

    /** @return list<string> delivery tags the driver deleted */
    public function deleted(): array
    {
        return $this->queue->deleted;
    }

    /**
     * Every backend touch in order — lets a test pin that a re-publish happens
     * BEFORE the delete, which counts alone cannot show.
     *
     * @return list<string>
     */
    public function events(): array
    {
        return $this->queue->events;
    }
}
