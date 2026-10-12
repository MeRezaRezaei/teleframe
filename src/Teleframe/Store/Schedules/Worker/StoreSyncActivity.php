<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use MeRezaRezaei\Teleframe\Store\StoreService;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * StoreSyncActivity — activity type `teleframe.store.registry-sweep`, the work
 * behind the `teleframe:tstore sync` schedule
 * (StoreMaintenanceScheduleSpec's `tstore-registry-sweep` entry).
 *
 * THE REAL WORK IS NOT REINVENTED. This delegates to
 * `StoreService::syncRegistry($configuredBuckets)`, which is exactly what
 * `php artisan teleframe:tstore sync` calls (TstoreCommand::sync() is a thin
 * `$this->info()` shell over exactly this call). The dialog walk, the title
 * classification, the `tf_store_buckets` upserts and the per-bucket
 * `ensureRegistry()` marker all live in that one method, so a scheduled sweep
 * and a manual sweep cannot diverge — which is the only property that makes
 * "recurring" safe here.
 *
 * WHERE THE BUCKET LIST COMES FROM — stated plainly, because it is the one
 * input the command takes from its caller. The command reads it from
 * `--buckets=`; a schedule has no command line at tick time, so it reads it
 * from the payload the spec hands through, under `buckets`, accepting either
 * the same comma-separated STRING the option takes or a plain list.
 *
 * THE DEFECT THAT FORCED A SECOND PATH, and the reason the precedence is
 * three-way. A schedule's payload is fixed SERVER-SIDE when the schedule is
 * armed: `SdkTemporalScheduleClient.php:254` builds
 * `StartWorkflowAction::new($activity)`, and no argument can be attached to a
 * TICK. So the ONLY way a bucket list reaches a scheduled sweep is the payload
 * a host wrote at install time — and `StoreMaintenanceScheduleSpec::installSpec()`
 * emits none, because the spec is cadence-only (see its
 * `tstore-registry-sweep` entry: `interval_seconds` and `activity`, nothing
 * else). An earlier version of this class therefore read `buckets` from a
 * payload that, for the sweep this package ships, never has one: the code path
 * existed, was tested, and was unreachable in production. A previous agent
 * flagged it and left it.
 *
 * So the fallback is WORKER-SIDE, and it is a FALLBACK, not a second
 * semantics. Temporal dispatches workflow → activity with the payload
 * forwarded verbatim (StoreSyncWorkflow::run() yields `$activity->sync($payload)`)
 * and the WORKER constructs this activity, so registration time is the one
 * place a host can introduce a list for a tick — @see TemporalWorker, whose
 * constructor carries it as its sixth parameter. Precedence, in one place
 * (@see self::bucketInput()):
 *   1. `schedule`        — the payload named `buckets`; it WINS, always. A
 *                          payload that names an empty list names an empty
 *                          list: that is the one way to say "register nothing
 *                          extra" even on a worker started with a default.
 *   2. `worker-default`  — the schedule named none and the worker was started
 *                          with a bucket list, normalised through the SAME
 *                          code path (self::buckets()) that a payload value
 *                          takes.
 *   3. `command-default` — neither did, so the list is EMPTY, which is the
 *                          command's own documented default: `teleframe:tstore
 *                          sync` with no `--buckets` passes an empty array and
 *                          sweeps whatever the dialog walk finds.
 *
 * WHY EVERY TICK REPORTS WHICH ONE (`buckets_source`). Three of these cases
 * produce the SAME list — an empty one — and an empty `requested_buckets` on a
 * job whose entire output is a count is indistinguishable from "the worker's
 * default was empty" and from "the operator configured a list that has not
 * arrived". A tick now names its own source, so a surprising `walked`/`upserted`
 * pair is diagnosable from the report instead of from a log archaeology. It
 * states where the list came from and nothing more; it does not claim the
 * sweep's results are correct.
 *
 * The account is still reached through the bound StoreService, never through a
 * value this activity carries — which is why the spec's docblock can still call
 * the sweep "needs the account's credentials … which is the operator's decision
 * to arm".
 *
 * THE CONTAINER SEAM. `StoreService` is container-bound as
 * `TstoreCommand::SERVICE_KEY` ('teleclient.tstore.service'), the SAME key
 * TstoreCommand::storeService() makes, so the scope's dialog lister, channel
 * factory and index are the ones the command uses. Resolved HERE, on the tick,
 * never in the constructor: this class constructs with no container and the
 * standalone-smoke gate stays green. With nothing bound, ContainerResolver
 * refuses BY NAME; a sweep reported as "0 walked, 0 upserted" against no
 * service would be a false all-clear on a job whose entire output is a count.
 *
 * `syncRegistry()`'s own `LogicException` for a StoreService with no dialog
 * lister is deliberately NOT caught: `teleframe:tstore sync` lets it through
 * too, and swallowing it here would turn a misconfigured sweep into a tick
 * that reports nothing found.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[ActivityInterface]
final class StoreSyncActivity
{
    /** The activity type registered on the worker and stubbed by the workflow. */
    public const TYPE = 'teleframe.store.registry-sweep';

    /**
     * Container seam name, so a host binds it by string and a test asserts it.
     *
     * Byte-identical to `TstoreCommand::SERVICE_KEY` by test, not by a shared
     * `use`: this string is the worker's contract with the host's container,
     * and the Worker directory stays free of a dependency on the Laravel command
     * that declares the same key.
     */
    public const SERVICE_SEAM = 'teleclient.tstore.service';

    /** The payload key the operator's optional bucket list arrives under. */
    public const BUCKETS_KEY = 'buckets';

    /**
     * `buckets_source` = the schedule's own payload named the list.
     *
     * One of three, and the only one where the payload is authoritative.
     *
     * @see self::SOURCE_WORKER_DEFAULT
     * @see self::SOURCE_COMMAND_DEFAULT
     */
    public const SOURCE_SCHEDULE = 'schedule';

    /**
     * `buckets_source` = the schedule named no list and the WORKER's fallback
     * supplied it. @see self::SOURCE_SCHEDULE for why a tick reports this.
     */
    public const SOURCE_WORKER_DEFAULT = 'worker-default';

    /**
     * `buckets_source` = neither named a list, so the list is EMPTY and what
     * ran is `php artisan teleframe:tstore sync` with no `--buckets`.
     *
     * Named rather than left implicit because it is the case a reader must NOT
     * mistake for a bug: an empty list is the command's own default, not a
     * misconfiguration this activity could have caught.
     */
    public const SOURCE_COMMAND_DEFAULT = 'command-default';

    /**
     * @param  list<string>  $defaultBuckets  the worker's own fallback list, used
     *                                        ONLY when the payload names none
     */
    public function __construct(
        private readonly ContainerResolver $resolver = new ContainerResolver,
        private readonly array $defaultBuckets = [],
    ) {}

    public static function options(): ActivityOptions
    {
        return ActivityOptions::new()
            // A sweep is a dialog walk against Telegram, so it is bounded by the
            // server's own patience rather than by a local constant.
            ->withStartToCloseTimeout(300)
            ->withRetryOptions(
                RetryOptions::new()
                    // A retried walk upserts the same rows again — the operation
                    // is idempotent but not free, and the cadence is a day away.
                    // One attempt: a second walk buys no correctness.
                    ->withMaximumAttempts(1),
            );
    }

    /**
     * Walk the dialogs, upsert the bucket rows, and report what changed.
     *
     * The report names which of the three sources the bucket list came from
     * (`buckets_source`) alongside the list itself, because a bare `[]` cannot
     * say whether the operator configured nothing or something never arrived.
     *
     * @param  array<string, mixed>  $payload  the spec entry's payload; may carry `buckets`
     * @return array<string, mixed>
     */
    #[ActivityMethod(name: self::TYPE)]
    public function sync(array $payload): array
    {
        [$raw, $source] = $this->bucketInput($payload);
        $buckets = $this->buckets($raw, $source);
        $report = $this->store()->syncRegistry($buckets);

        return [
            'requested_buckets' => $buckets,
            'walked' => $report['walked'],
            'upserted' => $report['upserted'],
            'buckets_source' => $source,
        ];
    }

    /**
     * The raw bucket-list value for this tick, and which of the three sources
     * it came from.
     *
     * THE PRECEDENCE LIVES HERE AND ONLY HERE, deliberately: the list that was
     * used and the source reported alongside it are returned by the same
     * decision, so the two can never drift into disagreeing. Deriving the
     * source in a second place is how a report ends up claiming `schedule` for
     * a list the worker default supplied.
     *
     * A payload key that is PRESENT wins even when it is an empty list — that
     * is how a host says "register nothing extra" on a worker started with a
     * default, and it is what a payload carrying `[]` has always meant here.
     * A payload value of `null` is absence, exactly as before.
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: mixed, 1: string} the raw value (null = use the empty list) and its source
     */
    private function bucketInput(array $payload): array
    {
        $raw = $payload[self::BUCKETS_KEY] ?? null;

        if ($raw !== null) {
            return [$raw, self::SOURCE_SCHEDULE];
        }

        if ($this->defaultBuckets !== []) {
            return [$this->defaultBuckets, self::SOURCE_WORKER_DEFAULT];
        }

        return [null, self::SOURCE_COMMAND_DEFAULT];
    }

    /**
     * The configured-bucket list, normalised exactly the way
     * TstoreCommand::sync() normalises `--buckets`.
     *
     * Same explode/trim/filter, so a comma-separated string and a list reach
     * `syncRegistry()` as the same list the command builds. ONE path for all
     * three sources: the schedule payload, the worker's fallback, and the
     * command's own empty default all arrive here, so a worker default cannot
     * be trimmed by rules the payload is not. Anything else is refused by name
     * rather than cast: a bucket list is what a sweep asserts exists in the
     * registry, so guessing one is a false record.
     *
     * `$source` names where the value came from in the refusal, because the
     * operator has to know WHICH input to fix.
     *
     * @return list<string>
     */
    private function buckets(mixed $raw, string $source): array
    {
        if ($raw === null) {
            // The command's own default: `teleframe:tstore sync` with no
            // --buckets passes an empty array.
            return [];
        }

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        is_array($raw) or throw new \RuntimeException(sprintf(
            'A %s sweep took its %s list from the %s and found a value of type %s. The command takes the same list '
            .'as a comma-separated --buckets string (or nothing at all), so a tick cannot be handed a bucket list '
            .'the manual run could not have produced.',
            'teleframe:tstore sync',
            self::BUCKETS_KEY,
            $source,
            get_debug_type($raw),
        ));

        $buckets = [];

        foreach ($raw as $bucket) {
            is_scalar($bucket) or throw new \RuntimeException(sprintf(
                'A %s sweep took its %s list from the %s and found a %s where a bucket name belongs. The command '
                .'takes a comma-separated --buckets string, so every element must be a name; dropping the '
                .'unusable one silently would leave a registry row the operator believes was registered.',
                'teleframe:tstore sync',
                self::BUCKETS_KEY,
                $source,
                get_debug_type($bucket),
            ));

            $name = trim((string) $bucket);

            if ($name !== '') {
                $buckets[] = $name;
            }
        }

        return $buckets;
    }

    /**
     * The StoreService the command would have made — see StorePruneActivity for
     * why the seam is a literal and why the type is checked.
     */
    private function store(): StoreService
    {
        $service = $this->resolver->resolve(self::SERVICE_SEAM);

        $service instanceof StoreService or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not a %s. `php artisan teleframe:tstore sync` would fail '
            .'the same way; the worker is not a second, laxer path.',
            self::SERVICE_SEAM,
            $service::class,
            StoreService::class,
        ));

        return $service;
    }
}
