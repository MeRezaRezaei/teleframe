<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Queue;

/**
 * Upload queue seam (verbatim S9): put() work is either dispatched inline
 * (SyncUploadQueue — "none queue" verbatim option, the default) or handed
 * to RabbitMQ (AmqpUploadQueue). enqueue returns a job id; consume drains
 * up to $max jobs, invoking the handler for each and returning how many
 * jobs were actually handled. The contract is deliberately tiny so the
 * queue driver can be swapped behind Teleframe::store()->put().
 */
interface UploadQueueContract
{
    /** @return string job id */
    public function enqueue(array $payload): string;

    /**
     * Drain up to $max jobs; handler receives the decoded payload.
     * Returns the number of jobs handled (acked / executed).
     *
     * @param  callable(array<string, mixed>): void  $handler
     */
    public function consume(callable $handler, int $max = 1): int;

    /**
     * True when the driver is a real external queue (RabbitMQ) rather
     * than the inline sync driver — teleframe:tstore work gates on this.
     */
    public function isAsync(): bool;
}
