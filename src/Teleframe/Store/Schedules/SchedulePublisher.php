<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

/**
 * Schedule publisher seam (verbatim S10, plan W8): installs and inspects
 * the maintenance schedules that keep the store converged — the index
 * sweep, the hot-cache prune, and (for data channels) the media index.
 * The null driver ships as the default (schedules off); hosts rebind a
 * real publisher (TemporalSchedulePublisher wired to 127.0.0.1:7233).
 */
interface SchedulePublisher
{
    /** Install or refresh the configured schedules. Returns per-schedule status. */
    public function install(array $schedules): array;

    /** Human-readable current state (CLI `teleframe:tstore schedules`). */
    public function describe(): string;
}
