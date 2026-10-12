<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Queue;

/**
 * SyncUploadQueue (verbatim S9): the "none queue" option — put() invokes
 * the upload handler inline, in the caller's process. This is the default
 * driver (tstore.queue.driver = sync): no broker, no worker, correct
 * ordering, and the store works on a plain login. enqueue hands the job
 * to the handler immediately; consume() is a no-op drain (an async
 * worker asking a sync driver for jobs finds none).
 */
final class SyncUploadQueue implements UploadQueueContract
{
    /** @param  \Closure(array<string, mixed>): void|null  $handler */
    public function __construct(private readonly ?\Closure $handler = null) {}

    public function enqueue(array $payload): string
    {
        $job = UploadJob::fromArray($payload);
        if ($this->handler !== null) {
            ($this->handler)($job->toArray());
        }

        return (string) $job->accountId; // job id: sync path identifies by account/target
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0; // nothing pending in a sync driver
    }

    public function isAsync(): bool
    {
        return false;
    }
}
