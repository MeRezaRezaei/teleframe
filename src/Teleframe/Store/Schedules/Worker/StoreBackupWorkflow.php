<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

/**
 * StoreBackupWorkflow — the workflow behind workflow type
 * `teleframe:tstore backup` (BackupScheduleSpec::installSpec()).
 *
 * The type string is NOT invented here: it is the string
 * `SdkTemporalScheduleClient` hands to `StartWorkflowAction::new()`, which is
 * `BackupScheduleSpec`'s `activity` value. @see WorkflowTypeCatalog::STORE_BACKUP
 *
 * A Temporal schedule's action starts a WORKFLOW, not an activity. So this
 * class exists purely to receive the tick and delegate the real work to the
 * `teleframe.store.backup-dump` activity, where the Laravel container is
 * resolved from. Putting the container lookup in the workflow would make the
 * workflow non-deterministic (Temporal replays it), which is why the split is
 * workflow → activity and not workflow → service.
 *
 * THE ATTRIBUTES ARE SAFE WITHOUT THE SDK. PHP resolves attribute classes
 * lazily — only when something reflects them — so this file LOADS FINE with
 * temporal/sdk absent (it is require-DEV here, on purpose). Registration is
 * what needs the SDK, and that only happens behind TemporalWorker's guard.
 *
 * NOT PROVEN: nothing here has been run against a live Temporal server. The
 * type registration is asserted against the real SDK's own WorkflowReader in
 * tests/Store/TemporalWorkerRegistrationTest.php; "a server dispatches a tick
 * to this handler and the activity runs" is not verified by anything in this
 * repo. Treat live behaviour as untested.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[WorkflowInterface]
final class StoreBackupWorkflow
{
    /**
     * @param  array<string, mixed>  $payload  the spec entry's payload, forwarded verbatim
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    #[WorkflowMethod(name: WorkflowTypeCatalog::STORE_BACKUP)]
    public function run(array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            StoreBackupDumpActivity::class,
            StoreBackupDumpActivity::options(),
        );

        return yield $activity->dump($payload);
    }
}
