<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * StoreBackupDumpActivity — activity type `teleframe.store.backup-dump`, the
 * work behind the `teleframe:tstore backup` schedule.
 *
 * THE REAL WORK IS NOT REINVENTED. This delegates to the same
 * `DatabaseBackupService::backup()` that `php artisan teleframe:tstore backup`
 * calls (TstoreCommand::backup() is a thin table-printing shell over exactly
 * this call), so a scheduled dump and a manual dump go through one code path.
 *
 * THE CONTAINER SEAM. `backup()` requires an explicit `DatabaseTarget` and the
 * service itself is container-bound. Both are resolved HERE, through the
 * ContainerResolver, on the tick — never in the constructor, so this class
 * constructs with no container and the standalone-smoke gate stays green.
 *
 * The report is returned as a plain array: an activity's return value is
 * serialised by the SDK's data converter, and `BackupRunReport` is a value
 * object whose `toArray()` is exactly what the CLI prints.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[ActivityInterface]
final class StoreBackupDumpActivity
{
    /** The activity type registered on the worker and stubbed by the workflow. */
    public const TYPE = 'teleframe.store.backup-dump';

    /** Container seam names, so a host binds them by string and a test asserts them. */
    public const SERVICE_SEAM = 'teleclient.store.backup.service';

    public const TARGET_SEAM = 'teleclient.store.backup.target';

    public function __construct(
        private readonly ContainerResolver $resolver = new ContainerResolver,
    ) {}

    /**
     * The activity's own options. Static so the workflow and the registration
     * agree on one set.
     */
    public static function options(): ActivityOptions
    {
        return ActivityOptions::new()
            // A database dump is I/O, not compute: a long ceiling is honest,
            // and the SDK's default of "sum of the other two" is not.
            ->withStartToCloseTimeout(3600)
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(1),
            );
    }

    /**
     * Run one backup dump and report what happened.
     *
     * @param  array<string, mixed>  $payload  the spec entry's payload
     * @return array<string, mixed>
     */
    #[ActivityMethod(name: self::TYPE)]
    public function dump(array $payload): array
    {
        $service = $this->resolver->resolve(self::SERVICE_SEAM);

        $service instanceof DatabaseBackupService or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not a %s. `php artisan teleframe:tstore backup` '
            .'would fail the same way; the worker is not a second, laxer path.',
            self::SERVICE_SEAM,
            $service::class,
            DatabaseBackupService::class,
        ));

        $queued = (bool) ($payload['queued'] ?? false);

        $report = $service->backup($this->target(), $queued);

        return $this->report($report);
    }

    /**
     * The DatabaseTarget the dump runs against. Resolved from its own seam so
     * `backup()`'s "requires an explicit DatabaseTarget" contract is satisfied
     * by configuration, not by a config-free default invented here.
     */
    private function target(): DatabaseTarget
    {
        $target = $this->resolver->resolve(self::TARGET_SEAM);

        $target instanceof DatabaseTarget or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not a %s.',
            self::TARGET_SEAM,
            $target::class,
            DatabaseTarget::class,
        ));

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function report(BackupRunReport $report): array
    {
        $rows = $report->toArray();

        return [
            'name' => $report->name,
            'bucket' => $report->bucket,
            'bytes' => $report->bytes,
            'chunks' => $report->chunks,
            'mode' => $report->mode,
            'queued' => $report->queued,
            'report' => $rows,
        ];
    }
}
