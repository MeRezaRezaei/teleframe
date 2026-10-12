<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

/**
 * RecurringScheduleClient — the ADDITIVE half of the Temporal boundary.
 *
 * TemporalScheduleClient (one method, startWorkflow) can only ever start a
 * SINGLE workflow run. It structurally cannot express "every N seconds",
 * which is the entire point of the schedules the user's verbatim asks for
 * ("make a backup from it in schedules" / "temporral for shcduls"). An audit
 * of the Cycle 19 claim found the publisher calling startWorkflow ONCE per
 * spec entry and DISCARDING the `interval_seconds` every spec producer
 * (BackupScheduleSpec, AppCallSpec) emits — so the "recurring" dump was a
 * one-shot run and the cadence was silently dropped.
 *
 * This interface is the missing half, kept SEPARATE from TemporalScheduleClient
 * rather than added to it, on purpose:
 *   - additive, so every existing TemporalScheduleClient implementor keeps
 *     working untouched (including third-party hosts);
 *   - it makes recurring-ness a property the HOST must actually provide,
 *     instead of a word in a config comment. A publisher given only the
 *     one-shot client will SAY in its status that the run is not recurring,
 *     rather than reporting success.
 *
 * A host implements this against temporal/sdk (which is require-DEV here, and
 * deliberately NOT a production dependency of this package — the dependency
 * surface is kept tiny on purpose). This interface is the contract such an
 * implementation must satisfy; no production implementor ships in-tree,
 * because shipping one would mean shipping the SDK.
 *
 * Plain-PHP, no container, zero regex.
 */
interface RecurringScheduleClient extends TemporalScheduleClient
{
    /**
     * Create or UPDATE (idempotently) a RECURRING schedule that fires
     * `activity` every `intervalSeconds`, and return the registered
     * schedule id.
     *
     * Idempotent by `scheduleId`: calling it twice with the same id must
     * refresh the one schedule, not stack a second one — otherwise a
     * boot-time install would multiply the backup every deploy.
     */
    public function createOrUpdateSchedule(
        string $scheduleId,
        string $activity,
        int $intervalSeconds,
        string $taskQueue,
        array $args = [],
    ): string;
}
