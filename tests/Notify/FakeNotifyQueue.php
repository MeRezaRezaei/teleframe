<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Contracts\Queue\Queue;
use MeRezaRezaei\Teleframe\Laravel\Notify\QueuedNotifyJob;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;

/**
 * FakeNotifyQueue — the smallest stand-in for an
 * Illuminate\Contracts\Queue\Queue that the notify driver can drain: it records
 * what was pushed, hands back envelopes carrying a REAL
 * serialize()/unserialize() round-trip of the queued object (so the driver's
 * hydrate() is exercised exactly as a worker would), and records deletions.
 * No backend, no daemon, no database, no broker.
 */
final class FakeNotifyQueue implements Queue
{
    /** @var list<array{queue: string, payload: object}> what enqueue() pushed, in order */
    public array $pushed = [];

    /** @var list<string> delivery tags deleted, in order */
    public array $deleted = [];

    /** @var list<array<string, mixed>> envelopes still waiting to be popped */
    private array $envelopes = [];

    /**
     * @var list<string> every backend touch in order ('push:<queue>',
     *                   'delete:<tag>') — lets a test pin ORDER (republish before delete), not
     *                   just counts, which is the property that actually matters.
     */
    public array $events = [];

    public function size($queue = null)
    {
        return count($this->envelopes);
    }

    public function pendingSize($queue = null)
    {
        return $this->size($queue);
    }

    public function delayedSize($queue = null)
    {
        return 0;
    }

    public function reservedSize($queue = null)
    {
        return 0;
    }

    public function creationTimeOfOldestPendingJob($queue = null)
    {
        return null;
    }

    public function push($job, $data = '', $queue = null)
    {
        $this->pushed[] = ['queue' => (string) $queue, 'payload' => $job];

        return null;
    }

    public function pushOn($queue, $job, $data = '')
    {
        $this->events[] = 'push:'.(string) $queue;

        return $this->push($job, $data, $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->pushed[] = ['queue' => (string) $queue, 'payload' => $payload];

        return null;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->pushOn($queue, $job, $data);
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }

        return null;
    }

    public function pop($queue = null): ?FakeNotifyJob
    {
        $envelope = array_shift($this->envelopes);
        if ($envelope === null) {
            return null;
        }

        return new FakeNotifyJob($envelope, 'tag-'.count($this->deleted), $this);
    }

    public function getConnectionName()
    {
        return 'fake';
    }

    public function setConnectionName($name)
    {
        return $this;
    }

    /**
     * Seed one delivered envelope for a queued job, carrying the wire shape a
     * worker reads: data.command = the serialized job object.
     */
    public function seed(NotificationJob $job): void
    {
        $this->envelopes[] = [
            'uuid' => 'seed-'.count($this->envelopes),
            'data' => [
                'commandName' => QueuedNotifyJob::class,
                'command' => serialize(new QueuedNotifyJob($job->toArray())),
            ],
        ];
    }

    /**
     * Seed an envelope that is NOT ours (hand-edited row, another producer,
     * a truncated write) so the driver's loud-failure path can be asserted.
     *
     * @param  array<string, mixed>  $payload
     */
    public function seedRaw(array $payload): void
    {
        $this->envelopes[] = $payload;
    }

    /** @return list<array<string, mixed>> envelopes still waiting */
    public function remaining(): array
    {
        return $this->envelopes;
    }

    public function recordDelete(string $deliveryTag): void
    {
        $this->events[] = 'delete:'.$deliveryTag;
        $this->deleted[] = $deliveryTag;
    }
}
