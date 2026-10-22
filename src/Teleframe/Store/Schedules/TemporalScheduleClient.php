<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

/**
 * The one-shot half of the Temporal boundary (Task 3, Cycle 19), kept as a
 * seam so schedule logic is testable without a Temporal server and
 * constructible without a container.
 *
 * HONEST SCOPE: startWorkflow() starts a SINGLE run. It cannot express a
 * cadence, so a publisher given only this interface cannot arm a recurring
 * schedule. Recurring-ness lives in the additive RecurringScheduleClient
 * (extends this). Do not read "the schedule publisher" as "the recurring
 * schedule" — the seam is only the former until a client that can recur is
 * bound.
 *
 * The package ships ONE implementor of the recurring half,
 * SdkTemporalScheduleClient, over temporal/sdk as an OPTIONAL runtime
 * dependency; a host that prefers its own connection strategy binds one and it
 * wins. temporal/sdk itself stays require-DEV here on purpose, so the
 * production dependency surface is not widened for a convenience.
 */
interface TemporalScheduleClient
{
    public function startWorkflow(string $workflow, string $taskQueue, array $args): string;
}
