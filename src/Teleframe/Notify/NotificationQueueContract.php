<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Notification queue seam (design doc §5): enqueue() hands a
 * NotificationJob to the driver; consume() drains up to $max jobs,
 * invoking the handler for each and returning how many were actually
 * handled. The shape mirrors the store UploadQueueContract
 * (SyncNotificationQueue inline default / AMQP broker), plus one addition:
 * deadLetterName() — the escape hatch naming the wire key where a job
 * that still fails after the retry cap is parked for operator
 * inspection (tframe.notify.dl — an opaque key, never renamed). The
 * contract is deliberately tiny so the driver can be swapped behind
 * NotifyService without touching callers.
 */
interface NotificationQueueContract
{
    /** @return string job id */
    public function enqueue(NotificationJob $job): string;

    /**
     * Drain up to $max jobs; the handler receives the hydrated job and
     * owns failures (it logs + rethrows, or the driver dead-letters).
     * Returns the number of jobs handled (acked / executed).
     *
     * @param  callable(NotificationJob): mixed  $handler
     */
    public function consume(callable $handler, int $max = 1): int;

    /**
     * True when the driver is a real external queue (AMQP) rather than
     * the inline sync driver — the CLI `work` drain gates on this.
     */
    public function isAsync(): bool;

    /**
     * The dead-letter escape: the wire key (queue name) where a job
     * that exhausted its retries lands. Every driver answers this
     * identically (sync names the canonical key too, even though it
     * never parks a job) so callers get one answer regardless of driver.
     */
    public function deadLetterName(): string;
}
