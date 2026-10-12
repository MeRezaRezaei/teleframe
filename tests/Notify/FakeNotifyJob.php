<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Queue\Jobs\Job;

/**
 * FakeNotifyJob — one delivered envelope handed back by FakeNotifyQueue::pop().
 * It is a real Illuminate\Queue\Jobs\Job, so the driver's read path
 * (`$envelope->payload()`, `$envelope->delete()`) is exercised against the
 * framework's own envelope contract rather than a hand-rolled double.
 */
final class FakeNotifyJob extends Job
{
    /**
     * @param  array<string, mixed>  $envelope  the queue row's payload
     */
    public function __construct(
        private readonly array $envelope,
        private readonly string $deliveryTag,
        private readonly FakeNotifyQueue $backing,
    ) {}

    public function getRawBody(): string
    {
        return (string) json_encode($this->envelope, JSON_THROW_ON_ERROR);
    }

    public function uuid(): string
    {
        return (string) ($this->envelope['uuid'] ?? 'seed');
    }

    public function getJobId(): string
    {
        return $this->deliveryTag;
    }

    public function getQueue(): ?string
    {
        return \MeRezaRezaei\Teleframe\Laravel\Notify\LaravelQueueNotificationQueue::QUEUE;
    }

    public function getConnectionName(): ?string
    {
        return 'fake';
    }

    public function delete(): void
    {
        $this->backing->recordDelete($this->deliveryTag);
    }
}
