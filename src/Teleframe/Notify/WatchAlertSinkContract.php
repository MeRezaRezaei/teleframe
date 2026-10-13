<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * WatchAlertSinkContract — the OUT-OF-BAND half of a no-show alert.
 *
 * THE DEFECT THIS SEAM EXISTS FOR (F8, 2026-10-13). NoShowWatchdog::emit()
 * raised its finding by `enqueue`-ing on `{topic}.missing` — through the very
 * queue and transport it was monitoring. When a queue or transport failure IS
 * the reason the heartbeat went missing, which is the operator's own scenario
 * ("if something did not come into a log channel as a result of failures"),
 * the alert died with the thing it was reporting: the watchdog could only say
 * so in a PSR-3 line that nobody outside the host can see. A watchdog whose
 * alert rides the failing channel is a comment, not a watchdog.
 *
 * So the alert has two paths with DIFFERENT failure domains:
 *   1. this sink — a durable local record, written FIRST and
 *      unconditionally, before any network or queue call is attempted;
 *   2. the `{topic}.missing` notification — best-effort on top, an
 *      ENHANCEMENT of the record, never its only path.
 *
 * That is why this is a seam and not a flag: a host that already ships
 * findings somewhere durable (a SIrel collector, a file shipper, a log store
 * the watchdog must not have to trust) binds its own implementation and the
 * guarantee is unchanged.
 */
interface WatchAlertSinkContract
{
    /**
     * Record $alert durably. Called BEFORE the notification is attempted and
     * regardless of whether one can be sent.
     *
     * @param  array<string, mixed>  $context  the alert context (topic, expected
     *                                         interval, last_seen, age)
     *
     * @throws \Throwable When the record could not be made durable. NoShowWatchdog
     *                    logs and continues to the notification path — a broken
     *                    sink must never be what suppresses the alert.
     */
    public function record(WatchAlert $alert, array $context): void;
}
