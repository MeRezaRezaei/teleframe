<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

/**
 * StorePruneWorkflow — the workflow behind workflow type
 * `teleframe:tstore prune` (StoreMaintenanceScheduleSpec::installSpec()).
 *
 * The type string is NOT invented here: it is the string
 * `SdkTemporalScheduleClient` hands to `StartWorkflowAction::new()`, which is
 * the spec's `activity` value, and a test asserts the catalog's constant is
 * byte-identical to what the spec emits. @see WorkflowTypeCatalog::TSTORE_PRUNE
 *
 * A Temporal schedule's action starts a WORKFLOW, not an activity. So this class
 * exists purely to receive the tick and delegate to the
 * `teleframe.store.hot-cache-prune` activity, where the StoreService is
 * resolved from the container. Putting the container lookup in the workflow
 * would make the workflow non-deterministic (Temporal replays it), which is why
 * the split is workflow → activity and not workflow → service.
 *
 * THE ATTRIBUTES ARE SAFE WITHOUT THE SDK. PHP resolves attribute classes
 * lazily — only when something reflects them — so this file LOADS FINE with
 * temporal/sdk absent (it is require-DEV here, on purpose). Registration is
 * what needs the SDK, and that only happens behind TemporalWorker's guard.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[WorkflowInterface]
final class StorePruneWorkflow
{
    /**
     * @param  array<string, mixed>  $payload  the spec entry's payload, forwarded verbatim
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    #[WorkflowMethod(name: WorkflowTypeCatalog::TSTORE_PRUNE)]
    public function run(array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            StorePruneActivity::class,
            StorePruneActivity::options(),
        );

        return yield $activity->prune($payload);
    }
}
