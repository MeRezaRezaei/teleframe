<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use MeRezaRezaei\Teleframe\Store\StoreService;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * StorePruneActivity — activity type `teleframe.store.hot-cache-prune`, the work
 * behind the `teleframe:tstore prune` schedule
 * (StoreMaintenanceScheduleSpec's `tstore-hot-cache-prune` entry).
 *
 * THE REAL WORK IS NOT REINVENTED. This delegates to `StoreService::prune()`,
 * which is exactly what `php artisan teleframe:tstore prune` calls
 * (TstoreCommand::prune() is a thin `$this->info()` shell over exactly this
 * call). `prune()` is itself one line — `$this->cache->evict()` — so "what the
 * activity prunes" and "what the command prunes" are the same object by
 * construction, not by a second implementation that could drift.
 *
 * WHY THAT MATTERS MORE THAN IT LOOKS. This schedule's whole reason to exist is
 * reclaiming hot-cache bytes once their TTL passes, on a cadence, with no human
 * present. An activity that swept something ELSE — a bucket listing, an index
 * row count, a remote walk — would tick green forever while the cache grew
 * without bound, and the growth would be invisible: the status line would say
 * served, the tick would succeed, and the only symptom would be memory. So the
 * one thing that must not be reimplemented here is the eviction itself.
 *
 * THE CONTAINER SEAM. `StoreService` is container-bound as
 * `TstoreCommand::SERVICE_KEY` ('teleclient.tstore.service'), which is the SAME
 * key TstoreCommand::storeService() makes — so a tick and a manual run share
 * one StoreService instance, one hot cache and one configuration. It is
 * resolved HERE, on the tick, never in the constructor, so this class
 * constructs with no container and the standalone-smoke gate stays green. With
 * nothing bound, ContainerResolver refuses BY NAME — which is the right answer:
 * reporting "0 evicted" for a cache that was never consulted is a false all-clear.
 *
 * The result is returned as a plain array: an activity's return value is
 * serialised by the SDK's data converter, and an int is the whole of what
 * `prune()` produces.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[ActivityInterface]
final class StorePruneActivity
{
    /** The activity type registered on the worker and stubbed by the workflow. */
    public const TYPE = 'teleframe.store.hot-cache-prune';

    /**
     * Container seam name, so a host binds it by string and a test asserts it.
     *
     * Spelled as a literal for the same reason StoreBackupDumpActivity's seams
     * are: this string is the contract between the worker and the host's
     * container, and the Worker directory stays free of a dependency on the
     * Laravel command that declares the same key. A test asserts it is
     * byte-identical to `TstoreCommand::SERVICE_KEY`, so the two cannot drift.
     */
    public const SERVICE_SEAM = 'teleclient.tstore.service';

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
            // A prune is an in-process map walk over the hot cache: seconds, and
            // local — no socket, no disk, no wire.
            ->withStartToCloseTimeout(60)
            ->withRetryOptions(
                RetryOptions::new()
                    // The cache is per-process and the next tick is a minute away,
                    // so a retry re-walks a cache that has already moved on. One
                    // attempt, and let the cadence be the retry.
                    ->withMaximumAttempts(1),
            );
    }

    /**
     * Evict the cold chunks from the hot cache and report how many went.
     *
     * @param  array<string, mixed>  $payload  the spec entry's payload; carries
     *                                         nothing this call reads — the command's
     *                                         `prune` action takes no option either
     * @return array<string, mixed>
     */
    #[ActivityMethod(name: self::TYPE)]
    public function prune(array $payload): array
    {
        $service = $this->store();

        return [
            'evicted' => $service->prune(),
        ];
    }

    /**
     * The StoreService the command would have made.
     *
     * Typed-checked rather than trusted: a seam bound to something else would
     * otherwise fail deep inside a method this activity did not call, and the
     * tick's report would name the wrong job.
     */
    private function store(): StoreService
    {
        $service = $this->resolver->resolve(self::SERVICE_SEAM);

        $service instanceof StoreService or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not a %s. `php artisan teleframe:tstore prune` would fail '
            .'the same way; the worker is not a second, laxer path.',
            self::SERVICE_SEAM,
            $service::class,
            StoreService::class,
        ));

        return $service;
    }
}
