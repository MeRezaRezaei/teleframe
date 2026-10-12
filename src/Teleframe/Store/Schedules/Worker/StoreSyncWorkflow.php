<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

/**
 * StoreSyncWorkflow — the workflow behind workflow type `teleframe:tstore sync`
 * (StoreMaintenanceScheduleSpec::installSpec()).
 *
 * The type string is NOT invented here: it is the string
 * `SdkTemporalScheduleClient` hands to `StartWorkflowAction::new()`, which is
 * the spec's `activity` value, and a test asserts the catalog's constant is
 * byte-identical to what the spec emits. @see WorkflowTypeCatalog::TSTORE_SYNC
 *
 * A Temporal schedule's action starts a WORKFLOW, not an activity, so this
 * class exists purely to receive the tick and delegate to the
 * `teleframe.store.registry-sweep` activity, where the StoreService — and
 * through it the scope's dialog lister — is resolved from the container. The
 * container lookup in a workflow would make the workflow non-deterministic
 * (Temporal replays it), which is why the split is workflow → activity and not
 * workflow → service.
 *
 * The payload is forwarded VERBATIM, not rebuilt: it may carry the operator's
 * `buckets` list, and the activity normalises it the way the command's
 * `--buckets` option is normalised. @see StoreSyncActivity
 *
 * The attributes are safe without the SDK: PHP resolves attribute classes
 * lazily, so this file LOADS FINE with temporal/sdk absent (it is require-DEV
 * here, on purpose). Registration is what needs the SDK.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[WorkflowInterface]
final class StoreSyncWorkflow
{
    /**
     * @param  array<string, mixed>  $payload  the spec entry's payload, forwarded verbatim
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    #[WorkflowMethod(name: WorkflowTypeCatalog::TSTORE_SYNC)]
    public function run(array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            StoreSyncActivity::class,
            StoreSyncActivity::options(),
        );

        return yield $activity->sync($payload);
    }
}
