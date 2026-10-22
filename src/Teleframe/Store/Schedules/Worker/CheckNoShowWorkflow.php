<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

/**
 * CheckNoShowWorkflow — the workflow behind workflow type
 * `teleclient.notify.check-no-show`.
 *
 * TWO producers emit that type and they must not drift:
 *   - src/Teleframe/Store/Backup/BackupScheduleSpec.php:62 (the backup
 *     watchdog-check schedule)
 *   - src/Teleframe/Notify/NotifyCommand.php:230 (`teleframe:notify schedules
 *     --install`)
 *
 * @see WorkflowTypeCatalog::CHECK_NO_SHOW
 *
 * Delegates to the `teleframe.notify.check-no-show` activity, where
 * NoShowWatchdog is resolved from the container.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[WorkflowInterface]
final class CheckNoShowWorkflow
{
    /**
     * @param  array<string, mixed>  $payload
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    #[WorkflowMethod(name: WorkflowTypeCatalog::CHECK_NO_SHOW)]
    public function run(array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            CheckNoShowActivity::class,
            CheckNoShowActivity::options(),
        );

        return yield $activity->check($payload);
    }
}
